<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
$pdo = db(); $uid = $user['id'];
$q = function (string $sql, array $p = []) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); };

$quizDone  = (int)$q("SELECT COUNT(*) FROM quiz_attempts WHERE user_id=? AND status IN ('submitted','graded')", [$uid]);
$avg       = round((float)$q("SELECT COALESCE(AVG(percentage),0) FROM quiz_attempts WHERE user_id=? AND status IN ('submitted','graded')", [$uid]), 1);
$asmDone   = (int)$q("SELECT COUNT(*) FROM assembly_attempts WHERE user_id=? AND status='completed'", [$uid]);
$certs     = (int)$q("SELECT COUNT(*) FROM certificates WHERE user_id=? AND status='valid'", [$uid]);
$totalItems = (int)$q('SELECT (SELECT COUNT(*) FROM computer_parts)+(SELECT COUNT(*) FROM reviewer_topics)+(SELECT COUNT(*) FROM learning_materials)');
$doneItems  = (int)$q('SELECT COUNT(*) FROM material_progress WHERE user_id=?', [$uid]);
$progress   = $totalItems ? min(100, (int)round(100 * $doneItems / $totalItems)) : 0;

$s = $pdo->prepare("(SELECT 'Quiz' k, CONCAT(q.title,' - ',a.percentage,'%') t, a.submitted_at d FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.user_id=? AND a.submitted_at IS NOT NULL)
  UNION ALL (SELECT 'Assembly', CONCAT(mode,' - ',percentage,'%'), completed_at FROM assembly_attempts WHERE user_id=? AND status='completed')
  ORDER BY d DESC LIMIT 6");
$s->execute([$uid, $uid]); $recent = $s->fetchAll();

$s = $pdo->prepare('SELECT p.id,p.name,p.icon,p.description FROM computer_parts p
  WHERE p.id NOT IN (SELECT item_id FROM material_progress WHERE user_id=? AND item_type="part") ORDER BY p.id LIMIT 4');
$s->execute([$uid]); $recommended = $s->fetchAll();

$ann = $pdo->query('SELECT title, body, created_at FROM announcements ORDER BY created_at DESC LIMIT 3')->fetchAll();
$first = explode(' ', $user['full_name'])[0];
$pageTitle = 'Dashboard'; $activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="welcome-card mb-4 d-flex align-items-center gap-3">
  <?php if ($user['picture']): ?><img class="avatar avatar-lg" src="<?= url('uploads/profiles/' . e($user['picture'])) ?>" alt="">
  <?php else: ?><span class="avatar avatar-lg avatar-ph"><?= e(strtoupper($first[0] ?? 'S')) ?></span><?php endif; ?>
  <div><h2 class="h4 mb-1">Welcome back, <?= e($first) ?>!</h2><p class="mb-0 opacity-75">Keep going. Every part you learn gets you closer to your certificate.</p></div>
</div>
<div class="row g-3 mb-4">
<?php foreach ([['Quizzes Completed',$quizDone,'fa-circle-question','primary'],['Average Quiz Score',$avg.'%','fa-percent','info'],['Assemblies Completed',$asmDone,'fa-screwdriver-wrench','warning'],['Certificates',$certs,'fa-certificate','success']] as [$l,$v,$i,$c]): ?>
  <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon bg-<?= $c ?>-soft text-<?= $c ?>"><i class="fa <?= $i ?>"></i></div>
  <div><div class="stat-value"><?= e($v) ?></div><div class="stat-label"><?= $l ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="card p-3 mb-4"><div class="d-flex justify-content-between"><strong>Learning Progress</strong><span><?= $progress ?>% (<?= $doneItems ?>/<?= $totalItems ?> topics)</span></div>
  <div class="progress mt-2" style="height:12px"><div class="progress-bar bg-info" style="width:<?= $progress ?>%"></div></div></div>
<div class="row g-2 mb-4">
<?php foreach ([['Start Learning','student/lessons/index.php','fa-play','primary'],['View Reviewer','student/reviewers/index.php','fa-book-open','info'],['Take a Quiz','student/quizzes/index.php','fa-circle-question','warning'],['Assembly Practice','student/assembly/index.php','fa-screwdriver-wrench','success'],['My Results','student/results/index.php','fa-square-poll-vertical','secondary'],['My Certificates','student/certificates/index.php','fa-certificate','dark']] as [$l,$h,$i,$c]): ?>
  <div class="col-6 col-md-4 col-xl-2"><a class="btn btn-outline-<?= $c ?> w-100 py-3" href="<?= url($h) ?>"><i class="fa <?= $i ?> d-block mb-1 fs-5"></i><?= $l ?></a></div>
<?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card p-3 h-100"><h6>Recommended Learning</h6>
  <?php if (!$recommended): ?><div class="empty-state"><i class="fa fa-trophy"></i><p>You have studied every component. Try the assembly assessment.</p></div><?php endif; ?>
  <?php foreach ($recommended as $r): ?>
    <a href="<?= url('student/lessons/view.php?id=' . $r['id']) ?>" class="rec-item"><i class="fa <?= e($r['icon'] ?: 'fa-microchip') ?>"></i>
    <div><strong><?= e($r['name']) ?></strong><small class="d-block text-muted"><?= e(mb_strimwidth($r['description'], 0, 70, '...')) ?></small></div></a>
  <?php endforeach; ?></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100"><h6>Recent Activity</h6>
  <?php if (!$recent): ?><div class="empty-state"><i class="fa fa-clock"></i><p>No activity yet.</p></div><?php endif; ?>
  <ul class="list-unstyled mb-0"><?php foreach ($recent as $r): ?>
    <li class="d-flex justify-content-between border-bottom py-1"><span><span class="badge text-bg-light"><?= e($r['k']) ?></span> <?= e($r['t']) ?></span><small class="text-muted"><?= e(date('M j', strtotime($r['d']))) ?></small></li>
  <?php endforeach; ?></ul>
  <?php if ($ann): ?><h6 class="mt-3">Announcements</h6><?php foreach ($ann as $a): ?>
    <div class="alert alert-info py-2 mb-2"><strong><?= e($a['title']) ?></strong><div class="small"><?= e($a['body']) ?></div></div><?php endforeach; endif; ?>
  </div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
