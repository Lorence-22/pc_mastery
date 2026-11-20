<?php
require_once __DIR__ . '/includes/functions.php';
$cert = null; $searched = false;
$no = strtoupper(trim($_GET['no'] ?? ''));
if ($no !== '') {
    $searched = true;
    $s = db()->prepare('SELECT c.*, CONCAT(sp.first_name," ",sp.last_name) AS student_name, sp.student_id FROM certificates c
        JOIN student_profiles sp ON sp.user_id=c.user_id WHERE c.certificate_no=?');
    $s->execute([$no]); $cert = $s->fetch();
}
$pageTitle = 'Verify Certificate'; $wide = false;
include __DIR__ . '/includes/auth_header.php';
?>
<h2 class="h4 fw-bold">Verify a Certificate</h2>
<form method="get" class="input-group mb-3"><input name="no" class="form-control form-control-lg" placeholder="e.g. PCM-2026-000001" value="<?= e($no) ?>" required>
<button class="btn btn-primary">Verify</button></form>
<?php if ($searched && !$cert): ?><div class="alert alert-danger">No certificate found with that number.</div>
<?php elseif ($cert): ?>
<div class="alert alert-<?= $cert['status']==='valid'?'success':'warning' ?>">
  <h5 class="alert-heading"><i class="fa fa-<?= $cert['status']==='valid'?'circle-check':'ban' ?>"></i>
  <?= $cert['status']==='valid' ? 'Authentic certificate' : 'This certificate has been REVOKED' ?></h5>
  <dl class="row mb-0">
    <dt class="col-5">Awarded to</dt><dd class="col-7"><?= e($cert['student_name']) ?></dd>
    <dt class="col-5">Title</dt><dd class="col-7"><?= e($cert['title']) ?></dd>
    <dt class="col-5">Score</dt><dd class="col-7"><?= e($cert['score']) ?>%</dd>
    <dt class="col-5">Issued</dt><dd class="col-7"><?= e(date('F j, Y', strtotime($cert['issued_at']))) ?></dd>
    <dt class="col-5">Number</dt><dd class="col-7"><?= e($cert['certificate_no']) ?></dd>
  </dl></div>
<?php endif; ?>
<a href="<?= url() ?>" class="btn btn-link w-100">Back to home</a>
<?php include __DIR__ . '/includes/auth_footer.php'; ?>
