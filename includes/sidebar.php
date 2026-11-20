<?php
$isAdmin = $user['role'] === 'admin';
$nav = $isAdmin ? [
  ['dashboard','Dashboard','fa-gauge','admin/dashboard.php'],
  ['students','Students','fa-user-graduate','admin/students/index.php'],
  ['quizzes','Quizzes','fa-circle-question','admin/quizzes/index.php'],
  ['reviewers','Reviewers & PDFs','fa-book-open','admin/reviewers/index.php'],
  ['parts','Computer Parts','fa-microchip','admin/computer_parts/index.php'],
  ['assembly','Assembly Assessment','fa-screwdriver-wrench','admin/assembly/index.php'],
  ['analytics','Analytics','fa-chart-line','admin/analytics/index.php'],
  ['certificates','Certificates','fa-certificate','admin/certificates/index.php'],
  ['settings','Settings','fa-gear','admin/settings/index.php'],
  ['logs','Activity Logs','fa-clock-rotate-left','admin/settings/logs.php'],
  ['profile','My Profile','fa-id-badge','admin/profile.php'],
] : [
  ['dashboard','Dashboard','fa-gauge','student/dashboard.php'],
  ['lessons','Computer Parts','fa-microchip','student/lessons/index.php'],
  ['reviewers','Reviewer','fa-book-open','student/reviewers/index.php'],
  ['quizzes','Quizzes','fa-circle-question','student/quizzes/index.php'],
  ['assembly','Assembly Practice','fa-screwdriver-wrench','student/assembly/index.php'],
  ['results','My Results','fa-square-poll-vertical','student/results/index.php'],
  ['progress','Learning Progress','fa-bars-progress','student/progress.php'],
  ['leaderboard','Leaderboard','fa-trophy','student/leaderboard.php'],
  ['certificates','My Certificates','fa-certificate','student/certificates/index.php'],
  ['profile','My Profile','fa-id-badge','student/profile.php'],
];
?>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= url($isAdmin ? 'admin/dashboard.php' : 'student/dashboard.php') ?>">
    <i class="fa fa-microchip"></i><span>PC Mastery</span>
  </a>
  <nav>
    <?php foreach ($nav as [$key,$label,$icon,$href]): ?>
      <a class="nav-item-link <?= ($activeNav ?? '') === $key ? 'active' : '' ?>" href="<?= url($href) ?>">
        <i class="fa <?= $icon ?>"></i><span><?= $label ?></span></a>
    <?php endforeach; ?>
  </nav>
  <a class="nav-item-link logout" href="<?= url('auth/logout.php') ?>"><i class="fa fa-right-from-bracket"></i><span>Logout</span></a>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
