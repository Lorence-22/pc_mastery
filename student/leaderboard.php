<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student'); $on = setting('leaderboard_enabled') === '1'; $rows = [];
if ($on) $rows = db()->query("SELECT sp.user_id, CONCAT(sp.first_name,' ',LEFT(sp.last_name,1),'.') name, sp.course,
  COALESCE((SELECT ROUND(AVG(percentage),1) FROM quiz_attempts WHERE user_id=sp.user_id AND status IN ('submitted','graded')),0) quiz_avg,
  COALESCE((SELECT MAX(percentage) FROM assembly_attempts WHERE user_id=sp.user_id AND status='completed'),0) asm,
  (SELECT COUNT(*) FROM certificates WHERE user_id=sp.user_id AND status='valid') certs
  FROM student_profiles sp JOIN users u ON u.id=sp.user_id WHERE u.status='active' ORDER BY (quiz_avg+asm)/2 DESC, certs DESC LIMIT 20")->fetchAll();
$pageTitle = 'Leaderboard'; $activeNav = 'leaderboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="card p-3"><?php if (!$on): ?><div class="empty-state"><i class="fa fa-trophy"></i><p>The leaderboard is turned off by your administrator.</p></div><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>#</th><th>Student</th><th>Course</th><th>Quiz avg</th><th>Assembly best</th><th>Certificates</th></tr></thead><tbody>
<?php foreach ($rows as $i => $r): ?><tr class="<?= $r['user_id'] == $user['id'] ? 'table-primary' : '' ?>"><td><?= $i < 3 ? '<i class="fa fa-medal" style="color:' . ['#F59E0B', '#94a3b8', '#b45309'][$i] . '"></i>' : $i + 1 ?></td><td><?= e($r['name']) ?></td><td><?= e($r['course']) ?></td><td><?= e($r['quiz_avg']) ?>%</td><td><?= e($r['asm']) ?>%</td><td><?= $r['certs'] ?></td></tr><?php endforeach; if (!$rows) echo '<tr><td colspan="6" class="text-muted">No data yet.</td></tr>'; ?></tbody></table></div><?php endif; ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
