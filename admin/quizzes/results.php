<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin'); $quizId = (int)($_GET['quiz'] ?? 0);
$s = db()->prepare('SELECT * FROM quizzes WHERE id=?'); $s->execute([$quizId]); $quiz = $s->fetch(); if (!$quiz) redirect('admin/quizzes/index.php');
$s = db()->prepare("SELECT a.*, sp.student_id sid, CONCAT(sp.last_name,', ',sp.first_name) name, sp.course, sp.section FROM quiz_attempts a JOIN student_profiles sp ON sp.user_id=a.user_id WHERE a.quiz_id=? AND a.status<>'in_progress' ORDER BY a.submitted_at DESC"); $s->execute([$quizId]); $rows = $s->fetchAll();
$pageTitle = 'Results: ' . $quiz['title']; $activeNav = 'quizzes';
include __DIR__ . '/../../includes/header.php';
?>
<a class="btn btn-outline-secondary mb-3" href="index.php"><i class="fa fa-arrow-left"></i> Quizzes</a>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Student</th><th>Course/Section</th><th>Attempt</th><th>Score</th><th>Status</th><th>Submitted</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?><div class="small text-muted"><?= e($r['sid']) ?></div></td><td><?= e($r['course'] . ' / ' . $r['section']) ?></td><td>#<?= $r['attempt_no'] ?></td>
<td><?= $r['percentage'] === null ? '-' : e($r['percentage']) . '%' ?></td><td><?= $r['status'] === 'needs_review' ? '<span class="badge text-bg-warning">Needs review</span>' : ($r['passed'] ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>') ?></td><td><?= fmt_dt($r['submitted_at']) ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="review.php?attempt=<?= $r['id'] ?>"><?= $r['status'] === 'needs_review' ? 'Grade' : 'Review' ?></a></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="7"><div class="empty-state"><i class="fa fa-inbox"></i><p>No submissions yet.</p></div></td></tr>'; ?></tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
