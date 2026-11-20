<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student'); $uid = $user['id'];
$mods = db()->query('SELECT m.*, (SELECT COUNT(*) FROM assembly_steps WHERE module_id=m.id) ns FROM assembly_modules m WHERE is_active=1')->fetchAll();
$hist = db()->prepare("SELECT a.*, c.id cert_id FROM assembly_attempts a LEFT JOIN certificates c ON c.attempt_id=a.id WHERE a.user_id=? AND a.status='completed' ORDER BY a.completed_at DESC LIMIT 15"); $hist->execute([$uid]); $hist = $hist->fetchAll();
$inprog = db()->prepare("SELECT module_id, mode FROM assembly_attempts WHERE user_id=? AND status='in_progress'"); $inprog->execute([$uid]); $running = []; foreach ($inprog->fetchAll() as $r) $running[$r['module_id'] . $r['mode']] = 1;
$pageTitle = 'Computer Assembly'; $activeNav = 'assembly';
include __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3 mb-3"><?php foreach ($mods as $m): ?><div class="col-lg-6"><div class="card p-4 h-100"><h5><i class="fa fa-screwdriver-wrench text-primary"></i> <?= e($m['title']) ?></h5><p class="text-muted"><?= e($m['description']) ?></p>
<div class="small mb-3"><span class="badge text-bg-light"><?= $m['ns'] ?> steps</span> <span class="badge text-bg-light">Passing score <?= (float)$m['passing_score'] ?>%</span></div>
<div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-primary" href="play.php?module=<?= $m['id'] ?>&mode=practice"><i class="fa fa-lightbulb"></i> <?= isset($running[$m['id'] . 'practice']) ? 'Resume practice' : 'Practice (with hints)' ?></a>
<a class="btn btn-primary" href="play.php?module=<?= $m['id'] ?>&mode=assessment" <?= isset($running[$m['id'] . 'assessment']) ? '' : 'data-confirm="Assessment mode has no hints or explanations. Passing earns your certificate. Start now?"' ?>><i class="fa fa-award"></i> <?= isset($running[$m['id'] . 'assessment']) ? 'Resume assessment' : 'Take Assessment' ?></a></div></div></div>
<?php endforeach; if (!$mods) echo '<div class="col-12"><div class="empty-state"><i class="fa fa-screwdriver-wrench"></i><p>No assembly modules are available.</p></div></div>'; ?></div>
<div class="card p-3"><h6>Attempt History</h6><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Date</th><th>Mode</th><th>Score</th><th>Result</th><th></th></tr></thead><tbody>
<?php foreach ($hist as $h): ?><tr><td><?= fmt_dt($h['completed_at']) ?></td><td class="text-capitalize"><?= e($h['mode']) ?></td><td><?= e($h['percentage']) ?>%</td><td><?= $h['passed'] ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>' ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="result.php?attempt=<?= $h['id'] ?>">Details</a> <?= $h['cert_id'] ? '<a class="btn btn-sm btn-success" href="../certificates/view.php?id=' . $h['cert_id'] . '"><i class="fa fa-certificate"></i> Certificate</a>' : '' ?></td></tr>
<?php endforeach; if (!$hist) echo '<tr><td colspan="5"><div class="empty-state"><i class="fa fa-inbox"></i><p>No attempts yet.</p></div></td></tr>'; ?></tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
