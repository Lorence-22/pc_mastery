<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/report_lib.php';
$user = require_login('admin');
$R = student_report((int)($_GET['id'] ?? 0)); if (!$R) { flash('danger', 'Student not found.'); redirect('admin/students/index.php'); }
$pageTitle = 'Student Profile'; $activeNav = 'students';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="index.php"><i class="fa fa-arrow-left"></i> Back</a><a class="btn btn-outline-primary" href="form.php?id=<?= $R['info']['user_id'] ?>"><i class="fa fa-pen"></i> Edit</a>
<a class="btn btn-outline-danger" href="../analytics/export.php?type=pdf&student=<?= $R['info']['user_id'] ?>"><i class="fa fa-file-pdf"></i> PDF report</a></div>
<?php include __DIR__ . '/../../includes/report_view.php'; include __DIR__ . '/../../includes/footer.php'; ?>
