<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student');
$s = db()->prepare("SELECT a.*, m.title, m.passing_score FROM assembly_attempts a JOIN assembly_modules m ON m.id=a.module_id WHERE a.id=? AND a.user_id=? AND a.status='completed'"); $s->execute([(int)($_GET['attempt'] ?? 0), $user['id']]); $a = $s->fetch();
if (!$a) { flash('error', 'Result not found.'); redirect('student/assembly/index.php'); }
$d = db()->prepare('SELECT s.step_no,s.title,s.step_type,s.points,d.points_earned,d.tries,d.in_sequence,d.hint_used FROM assembly_attempt_details d JOIN assembly_steps s ON s.id=d.step_id WHERE d.attempt_id=? ORDER BY s.step_no'); $d->execute([$a['id']]); $rows = $d->fetchAll();
$cat = ['safety' => ['Safety precautions', 0, 0], 'placement' => ['Component identification & placement', 0, 0], 'cable' => ['Proper cable connections', 0, 0], 'inspection' => ['Final inspection', 0, 0], 'test' => ['Initial boot test', 0, 0]];
$seq = 0; foreach ($rows as $r) { $cat[$r['step_type']][1] += $r['points_earned']; $cat[$r['step_type']][2] += $r['points']; $seq += $r['in_sequence'] ? 1 : 0; }
$cert = db()->prepare('SELECT id FROM certificates WHERE attempt_id=?'); $cert->execute([$a['id']]); $certId = $cert->fetchColumn();
$pageTitle = 'Assembly Result'; $activeNav = 'assembly';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4 text-center mb-3"><i class="fa fa-<?= $a['passed'] ? 'circle-check text-success' : 'circle-xmark text-danger' ?> fa-3x mb-2"></i><h4><?= $a['passed'] ? 'Passed' : 'Not passed yet' ?> <small class="text-muted text-capitalize">(<?= e($a['mode']) ?>)</small></h4>
<div class="display-5 fw-bold"><?= e($a['percentage']) ?>%</div><p class="text-muted">Passing score: <?= (float)$a['passing_score'] ?>% &middot; <?= e($a['title']) ?></p>
<div class="d-flex gap-2 justify-content-center flex-wrap"><?php if ($certId): ?><a class="btn btn-success" href="../certificates/view.php?id=<?= $certId ?>"><i class="fa fa-certificate"></i> View certificate</a><?php elseif ($a['mode'] === 'practice' && $a['passed']): ?><a class="btn btn-primary" href="play.php?module=<?= $a['module_id'] ?>&mode=assessment">Take the Assessment</a><?php endif; ?>
<a class="btn btn-outline-primary" href="play.php?module=<?= $a['module_id'] ?>&mode=<?= e($a['mode']) ?>">Try again</a><a class="btn btn-outline-secondary" href="index.php">Back</a></div></div>
<div class="row g-3 mb-3"><?php foreach ($cat as [$l, $e, $t]): if (!$t) continue; $pc = round(100 * $e / $t); ?><div class="col-md-6 col-xl-4"><div class="card p-3"><div class="d-flex justify-content-between"><span><?= $l ?></span><b><?= $pc ?>%</b></div><div class="progress mt-1" style="height:8px"><div class="progress-bar bg-<?= $pc >= 80 ? 'success' : ($pc >= 50 ? 'warning' : 'danger') ?>" style="width:<?= $pc ?>%"></div></div></div></div><?php endforeach; ?>
<div class="col-md-6 col-xl-4"><div class="card p-3"><div class="d-flex justify-content-between"><span>Assembly sequence</span><b><?= count($rows) ? round(100 * $seq / count($rows)) : 0 ?>%</b></div><div class="progress mt-1" style="height:8px"><div class="progress-bar bg-info" style="width:<?= count($rows) ? round(100 * $seq / count($rows)) : 0 ?>%"></div></div></div></div></div>
<div class="card p-3"><h6>Step breakdown</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Step</th><th>Tries</th><th>Points</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?= $r['step_no'] ?></td><td><?= e($r['title']) ?> <?= $r['in_sequence'] ? '' : '<span class="badge text-bg-warning">out of order attempt</span>' ?></td><td><?= $r['tries'] ?></td><td><?= (float)$r['points_earned'] ?>/<?= (float)$r['points'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
