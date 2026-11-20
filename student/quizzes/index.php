<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/quiz_lib.php';
$user = require_login('student'); $uid = $user['id'];
$qs = trim($_GET['q'] ?? ''); $w = ['z.is_active=1']; $p = [$uid, $uid, $uid]; if ($qs !== '') { $w[] = '(z.title LIKE ? OR z.category LIKE ?)'; array_push($p, "%$qs%", "%$qs%"); }
$s = db()->prepare("SELECT z.*, (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=z.id AND user_id=? AND status<>'in_progress') used,
 (SELECT MAX(percentage) FROM quiz_attempts WHERE quiz_id=z.id AND user_id=? AND status IN ('submitted','graded')) best,
 (SELECT id FROM quiz_attempts WHERE quiz_id=z.id AND user_id=? AND status='in_progress' LIMIT 1) running,
 (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=z.id) nq, (SELECT id FROM reviewer_topics WHERE related_quiz_id=z.id LIMIT 1) topic_id
 FROM quizzes z WHERE " . implode(' AND ', $w) . ' ORDER BY z.category, z.title'); $s->execute($p); $rows = $s->fetchAll();
$pageTitle = 'Available Quizzes'; $activeNav = 'quizzes';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-10"><input name="q" class="form-control" placeholder="Search quizzes" value="<?= e($qs) ?>"></div><div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div></form></div>
<div class="row g-3"><?php foreach ($rows as $r): $closed = quiz_open($r); $left = $r['max_attempts'] - $r['used']; ?><div class="col-md-6 col-xl-4"><div class="card p-3 h-100"><div class="d-flex justify-content-between"><span class="badge text-bg-primary"><?= e($r['category']) ?></span><span class="badge text-bg-<?= $r['method'] === 'pdf' ? 'warning' : 'info' ?>"><?= $r['method'] === 'pdf' ? 'PDF quiz' : $r['nq'] . ' questions' ?></span></div>
<h5 class="mt-2"><?= e($r['title']) ?></h5><p class="small text-muted"><?= e($r['description']) ?></p>
<div class="small mb-2"><i class="fa fa-clock"></i> <?= $r['time_limit_minutes'] ?> min &middot; <i class="fa fa-bullseye"></i> pass <?= (float)$r['passing_score'] ?>% &middot; <i class="fa fa-rotate"></i> <?= max(0, $left) ?>/<?= $r['max_attempts'] ?> attempts left<?= $r['best'] !== null ? ' &middot; best ' . e($r['best']) . '%' : '' ?></div>
<div class="mt-auto d-flex gap-2"><?php if ($closed && !$r['running']): ?><button class="btn btn-secondary flex-fill" disabled><?= e($closed) ?></button><?php elseif ($r['running']): ?><a class="btn btn-warning flex-fill" href="take.php?quiz=<?= $r['id'] ?>">Resume</a><?php elseif ($left <= 0): ?><button class="btn btn-secondary flex-fill" disabled>No attempts left</button><?php else: ?><a class="btn btn-primary flex-fill" href="take.php?quiz=<?= $r['id'] ?>">Start quiz</a><?php endif; ?>
<?php if ($r['topic_id']): ?><a class="btn btn-outline-info" href="<?= url('student/reviewers/topic.php?id=' . $r['topic_id']) ?>" title="Study the reviewer first"><i class="fa fa-book-open"></i></a><?php endif; ?></div></div></div>
<?php endforeach; if (!$rows) echo '<div class="col-12"><div class="empty-state"><i class="fa fa-circle-question"></i><p>No quizzes available.</p></div></div>'; ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
