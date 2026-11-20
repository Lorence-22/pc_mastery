<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student'); $id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT t.*, z.title quiz_title, z.is_active qa FROM reviewer_topics t LEFT JOIN quizzes z ON z.id=t.related_quiz_id WHERE t.id=?'); $s->execute([$id]); $t = $s->fetch();
if (!$t) { flash('error', 'Topic not found.'); redirect('student/reviewers/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { require_csrf(); mark_progress($user['id'], 'topic', $id); log_activity('topic_completed', $t['title']); flash('success', 'Topic completed.'); redirect('student/reviewers/topic.php?id=' . $id); }
$m = db()->prepare('SELECT * FROM learning_materials WHERE topic_id=?'); $m->execute([$id]); $mats = $m->fetchAll();
$done = in_array($id, get_part_ids_done($user['id'], 'topic'), true);
$pageTitle = $t['title']; $activeNav = 'reviewers';
include __DIR__ . '/../../includes/header.php';
?>
<a class="btn btn-outline-secondary mb-3" href="index.php"><i class="fa fa-arrow-left"></i> Reviewer</a>
<div class="card p-4"><span class="badge text-bg-primary align-self-start mb-2"><?= e($t['category']) ?></span><h3><?= e($t['title']) ?></h3><hr>
<div class="lesson-text"><?= nl2br(e($t['content'])) ?></div>
<?php if ($mats): ?><hr><h6>Attached materials</h6><?php foreach ($mats as $mm): ?><a class="btn btn-sm btn-outline-primary me-1" href="material.php?id=<?= $mm['id'] ?>"><i class="fa fa-file"></i> <?= e($mm['title']) ?></a><?php endforeach; endif; ?>
<hr><div class="d-flex gap-2 flex-wrap"><form method="post"><?= csrf_field() ?><button class="btn btn-<?= $done ? 'success' : 'primary' ?>" <?= $done ? 'disabled' : '' ?>><i class="fa fa-check"></i> <?= $done ? 'Completed' : 'Mark as completed' ?></button></form>
<?php if ($t['related_quiz_id'] && $t['qa']): ?><a class="btn btn-warning" href="<?= url('student/quizzes/take.php?quiz=' . $t['related_quiz_id']) ?>"><i class="fa fa-circle-question"></i> Take quiz: <?= e($t['quiz_title']) ?></a><?php endif; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
