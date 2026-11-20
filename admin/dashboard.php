<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('admin');
$pageTitle = 'Admin Dashboard'; $activeNav = 'dashboard';
$extraScripts = '<script src="' . url('assets/js/admin_dashboard.js') . '"></script>';
include __DIR__ . '/../includes/header.php';
$cards = [
 ['students','Registered Students','fa-user-graduate','primary'],['active','Active Students','fa-user-check','success'],
 ['quizzes','Total Quizzes','fa-circle-question','info'],['materials','Learning Materials','fa-book-open','warning'],
 ['assembly','Assembly Assessments','fa-screwdriver-wrench','primary'],['certs','Certificates Issued','fa-certificate','success'],
 ['avg_score','Avg Quiz Score (%)','fa-percent','info'],['pass_rate','Passing Rate (%)','fa-circle-check','success']];
?>
<div class="row g-3 mb-4">
<?php foreach ($cards as [$k,$l,$i,$c]): ?>
  <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon bg-<?= $c ?>-soft text-<?= $c ?>"><i class="fa <?= $i ?>"></i></div>
    <div><div class="stat-value" data-stat="<?= $k ?>"><span class="placeholder-glow"><span class="placeholder col-4"></span></span></div><div class="stat-label"><?= $l ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-8"><div class="card p-3 h-100"><h6>Student Performance (Top 10 avg %)</h6><div class="chart-box"><canvas id="chPerf"></canvas></div></div></div>
  <div class="col-lg-4"><div class="card p-3 h-100"><h6>Passing vs. Failing</h6><div class="chart-box"><canvas id="chPF"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100"><h6>Quiz Results Overview (avg %)</h6><div class="chart-box"><canvas id="chQuiz"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100"><h6>Monthly Student Registration</h6><div class="chart-box"><canvas id="chReg"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100"><h6>Most Difficult Quiz Topics (% correct)</h6><div class="chart-box"><canvas id="chDiff"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100"><h6>Assembly Assessment Performance (avg %)</h6><div class="chart-box"><canvas id="chAsm"></canvas></div></div></div>
</div>
<div class="row g-3">
<?php foreach ([['students','New Students','fa-user-plus'],['quizzes','Recent Quizzes','fa-circle-question'],['assembly','Recent Assembly','fa-screwdriver-wrench'],['certs','Recent Certificates','fa-certificate']] as [$k,$t,$i]): ?>
  <div class="col-md-6"><div class="card p-3 h-100"><h6><i class="fa <?= $i ?> text-primary me-1"></i><?= $t ?></h6><ul class="list-unstyled mb-0 recent-list" data-recent="<?= $k ?>"><li class="text-muted">Loading...</li></ul></div></div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
