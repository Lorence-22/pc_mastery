<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student');
$s = db()->prepare('SELECT * FROM certificates WHERE user_id=? ORDER BY issued_at DESC'); $s->execute([$user['id']]); $rows = $s->fetchAll();
$pageTitle = 'My Certificates'; $activeNav = 'certificates';
include __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3"><?php foreach ($rows as $c): ?><div class="col-md-6 col-xl-4"><div class="card p-4 h-100 text-center"><i class="fa fa-certificate fa-3x <?= $c['status'] === 'valid' ? 'text-warning' : 'text-secondary' ?>"></i><h6 class="mt-2"><?= e($c['title']) ?></h6><div class="small text-muted">No. <?= e($c['certificate_no']) ?><br>Score <?= e($c['score']) ?>% &middot; <?= e(date('M j, Y', strtotime($c['issued_at']))) ?></div>
<?php if ($c['status'] === 'revoked'): ?><span class="badge text-bg-danger my-2">Revoked</span><?php endif; ?>
<div class="d-flex gap-1 justify-content-center mt-2"><a class="btn btn-sm btn-primary" href="view.php?id=<?= $c['id'] ?>"><i class="fa fa-eye"></i> View</a><a class="btn btn-sm btn-outline-danger" href="download.php?id=<?= $c['id'] ?>"><i class="fa fa-file-pdf"></i> PDF</a><a class="btn btn-sm btn-outline-secondary" target="_blank" href="view.php?id=<?= $c['id'] ?>&print=1"><i class="fa fa-print"></i> Print</a></div></div></div>
<?php endforeach; if (!$rows): ?><div class="col-12"><div class="empty-state"><i class="fa fa-certificate"></i><p>No certificates yet. Pass the Assembly Assessment to earn one.</p><a class="btn btn-primary" href="../assembly/index.php">Go to assembly</a></div></div><?php endif; ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
