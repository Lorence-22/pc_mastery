<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { require_csrf(); db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]); redirect('student/notifications.php'); }
$s = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50'); $s->execute([$user['id']]); $rows = $s->fetchAll();
$unread = array_filter($rows, fn($r) => !$r['is_read']); $ann = db()->query('SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5')->fetchAll();
$pageTitle = 'Notifications'; $activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3"><div class="col-lg-8"><div class="card p-3"><div class="d-flex justify-content-between mb-2"><h6 class="mb-0">Notification Center</h6><?php if ($unread): ?><form method="post"><?= csrf_field() ?><button class="btn btn-sm btn-outline-primary">Mark all as read</button></form><?php endif; ?></div>
<?php foreach ($rows as $r): ?><div class="d-flex justify-content-between border-bottom py-2 <?= $r['is_read'] ? 'text-muted' : 'fw-semibold' ?>"><span><?= $r['is_read'] ? '' : '<i class="fa fa-circle text-primary" style="font-size:.5rem"></i> ' ?><?= $r['link'] ? '<a href="' . e($r['link']) . '">' . e($r['message']) . '</a>' : e($r['message']) ?></span><small><?= fmt_dt($r['created_at']) ?></small></div><?php endforeach; if (!$rows) echo '<div class="empty-state"><i class="fa fa-bell"></i><p>No notifications.</p></div>'; ?></div></div>
<div class="col-lg-4"><div class="card p-3"><h6>Announcements</h6><?php foreach ($ann as $a): ?><div class="alert alert-info py-2"><b><?= e($a['title']) ?></b><div class="small"><?= e($a['body']) ?></div></div><?php endforeach; if (!$ann) echo '<small class="text-muted">None.</small>'; ?></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
