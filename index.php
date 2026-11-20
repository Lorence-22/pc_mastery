<?php
require_once __DIR__ . '/includes/functions.php';
$u = current_user();
$stats = ['parts' => 0, 'quizzes' => 0, 'students' => 0];
try {
    $stats['parts'] = (int)db()->query('SELECT COUNT(*) FROM computer_parts')->fetchColumn();
    $stats['quizzes'] = (int)db()->query('SELECT COUNT(*) FROM quizzes WHERE is_active=1')->fetchColumn();
    $stats['students'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
} catch (Throwable $e) {}
$home = $u ? ($u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php') : 'auth/login.php';
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PC Mastery | Learn PC Hardware &amp; Assembly</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet"></head><body>
<nav class="navbar navbar-expand-md landing-nav"><div class="container">
  <a class="navbar-brand fw-bold text-white" href="#"><i class="fa fa-microchip text-info"></i> PC Mastery</a>
  <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nv"><i class="fa fa-bars text-white"></i></button>
  <div class="collapse navbar-collapse" id="nv"><ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
    <li class="nav-item"><a class="nav-link text-white-50" href="#about">About</a></li>
    <li class="nav-item"><a class="nav-link text-white-50" href="<?= url('verify.php') ?>">Verify Certificate</a></li>
    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= url($home) ?>"><?= $u ? 'Dashboard' : 'Login' ?></a></li>
    <?php if (!$u): ?><li class="nav-item"><a class="btn btn-info btn-sm text-white" href="<?= url('auth/register.php') ?>">Register</a></li><?php endif; ?>
  </ul></div></div></nav>
<section class="hero"><div class="container text-center">
  <h1 class="display-5 fw-bold">Master the PC.<br>From parts to full assembly.</h1>
  <p class="lead mx-auto" style="max-width:680px">Learn computer hardware, practice with a validated drag-and-drop assembly simulator, take quizzes, and earn a verifiable certificate.</p>
  <a class="btn btn-info btn-lg text-white me-2" href="<?= url('auth/register.php') ?>">Get Started</a>
  <a class="btn btn-outline-light btn-lg" href="<?= url('auth/login.php') ?>">Login</a>
  <div class="row mt-5 g-3 justify-content-center">
    <?php foreach ([['Hardware Parts',$stats['parts'],'fa-microchip'],['Active Quizzes',$stats['quizzes'],'fa-circle-question'],['Learners',$stats['students'],'fa-user-graduate']] as [$l,$n,$i]): ?>
    <div class="col-6 col-md-3"><div class="hero-stat"><i class="fa <?= $i ?>"></i><div class="fs-3 fw-bold"><?= $n ?></div><small><?= $l ?></small></div></div>
    <?php endforeach; ?></div></div></section>
<section id="about" class="py-5"><div class="container"><h2 class="text-center fw-bold mb-4">About the System</h2>
<div class="row g-4">
<?php foreach ([
 ['fa-book-open','Learn','Illustrated lessons on 16 hardware components, reviewers, and PDF materials.'],
 ['fa-screwdriver-wrench','Assemble','An 18-step interactive assembly with sequence checking, saved to the database.'],
 ['fa-chart-line','Track','Progress, quiz history, and performance analytics for students and instructors.'],
 ['fa-certificate','Certify','Pass the assessment and get an auto-generated certificate with a unique number.']] as [$i,$t,$d]): ?>
<div class="col-md-6 col-lg-3"><div class="card feature-card h-100 p-4 text-center"><i class="fa <?= $i ?> fa-2x text-primary mb-3"></i><h5><?= $t ?></h5><p class="text-muted mb-0"><?= $d ?></p></div></div>
<?php endforeach; ?></div></div></section>
<footer class="text-center py-4 text-muted">&copy; <?= date('Y') ?> PC Mastery</footer>
</body></html>
