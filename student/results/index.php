<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student'); $uid = $user['id'];
$qa = db()->prepare("SELECT a.*, z.title FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE a.user_id=? AND a.status<>'in_progress' ORDER BY a.submitted_at DESC"); $qa->execute([$uid]); $quiz = $qa->fetchAll();
$aa = db()->prepare("SELECT a.*, m.title FROM assembly_attempts a JOIN assembly_modules m ON m.id=a.module_id WHERE a.user_id=? AND a.status='completed' ORDER BY a.completed_at DESC"); $aa->execute([$uid]); $asm = $aa->fetchAll();
$pageTitle = 'My Results'; $activeNav = 'results';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><h6>Quiz History</h6><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Quiz</th><th>Attempt</th><th>Score</th><th>Result</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach ($quiz as $r): ?><tr><td><?= e($r['title']) ?></td><td>#<?= $r['attempt_no'] ?></td><td><?= $r['percentage'] === null ? '-' : e($r['percentage']) . '%' ?></td><td><?= $r['status'] === 'needs_review' ? '<span class="badge text-bg-warning">Pending review</span>' : ($r['passed'] ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>') ?></td><td><?= fmt_dt($r['submitted_at']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="../quizzes/result.php?attempt=<?= $r['id'] ?>">View</a></td></tr>
<?php endforeach; if (!$quiz) echo '<tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><p>No quizzes taken yet.</p></div></td></tr>'; ?></tbody></table></div></div>
<div class="card p-3"><h6>Assembly History</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Module</th><th>Mode</th><th>Score</th><th>Result</th><th>Date</th></tr></thead><tbody>
<?php foreach ($asm as $r): ?><tr><td><?= e($r['title']) ?></td><td class="text-capitalize"><?= e($r['mode']) ?></td><td><?= e($r['percentage']) ?>%</td><td><?= $r['passed'] ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>' ?></td><td><?= fmt_dt($r['completed_at']) ?></td></tr>
<?php endforeach; if (!$asm) echo '<tr><td colspan="5"><div class="empty-state"><i class="fa fa-inbox"></i><p>No assembly attempts yet.</p></div></td></tr>'; ?></tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
