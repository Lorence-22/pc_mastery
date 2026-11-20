<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student'); $id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT * FROM learning_materials WHERE id=?'); $s->execute([$id]); $m = $s->fetch();
if (!$m) { flash('error', 'File not found.'); redirect('student/reviewers/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { require_csrf(); mark_progress($user['id'], 'material', $id); flash('success', 'Marked as completed.'); redirect('student/reviewers/material.php?id=' . $id); }
$done = in_array($id, get_part_ids_done($user['id'], 'material'), true); $src = url('api/file.php?t=material&id=' . $id);
$pageTitle = $m['title']; $activeNav = 'reviewers';
include __DIR__ . '/../../includes/header.php';
?>
<a class="btn btn-outline-secondary mb-3" href="index.php"><i class="fa fa-arrow-left"></i> Reviewer</a>
<div class="card p-3"><p><?= e($m['description']) ?></p>
<?php if ($m['file_type'] === 'pdf'): ?><iframe src="<?= $src ?>" style="width:100%;height:75vh;border:1px solid #e2e8f0;border-radius:10px"></iframe><?php else: ?><img src="<?= $src ?>" class="img-fluid rounded"><?php endif; ?>
<div class="d-flex gap-2 mt-3"><form method="post"><?= csrf_field() ?><button class="btn btn-<?= $done ? 'success' : 'primary' ?>" <?= $done ? 'disabled' : '' ?>><i class="fa fa-check"></i> <?= $done ? 'Completed' : 'Mark as completed' ?></button></form>
<?php if ($m['downloadable']): ?><a class="btn btn-outline-primary" href="<?= $src ?>&dl=1"><i class="fa fa-download"></i> Download</a><?php endif; ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
