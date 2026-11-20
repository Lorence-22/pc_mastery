<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/quiz_lib.php';
$user = require_login('student');
$s = db()->prepare("SELECT a.*, z.title, z.method, z.passing_score, z.id qid FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE a.id=? AND a.user_id=? AND a.status<>'in_progress'"); $s->execute([(int)($_GET['attempt'] ?? 0), $user['id']]); $at = $s->fetch();
if (!$at) { flash('error', 'Result not found.'); redirect('student/results/index.php'); }
$pageTitle = 'Quiz Result'; $activeNav = 'results';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4 text-center mb-3">
<?php if ($at['status'] === 'needs_review'): ?><i class="fa fa-hourglass-half fa-3x text-warning mb-2"></i><h4>Submitted for grading</h4><p class="text-muted">Your instructor will grade <b><?= e($at['title']) ?></b>. You will be notified.</p>
<?php else: ?><i class="fa fa-<?= $at['passed'] ? 'circle-check text-success' : 'circle-xmark text-danger' ?> fa-3x mb-2"></i><h4><?= $at['passed'] ? 'Passed!' : 'Not passed' ?></h4>
<div class="display-5 fw-bold"><?= e($at['percentage']) ?>%</div><p class="text-muted mb-0"><?= e($at['title']) ?> &middot; <?= (float)$at['score'] ?>/<?= (float)$at['total_points'] ?> points &middot; passing <?= (float)$at['passing_score'] ?>%</p>
<?php if ($at['admin_feedback']): ?><div class="alert alert-info mt-3 text-start"><b>Instructor feedback:</b> <?= e($at['admin_feedback']) ?></div><?php endif; endif; ?>
<div class="mt-3"><a class="btn btn-primary" href="index.php">Back to quizzes</a> <a class="btn btn-outline-secondary" href="../results/index.php">My results</a></div></div>
<?php if ($at['status'] === 'graded' && $at['method'] === 'builder'):
  $qs = load_questions((int)$at['qid']); $m = db()->prepare('SELECT * FROM quiz_answers WHERE attempt_id=?'); $m->execute([$at['id']]); $ans = []; foreach ($m->fetchAll() as $r) $ans[$r['question_id']] = $r; ?>
<h6>Answer review</h6><?php foreach ($qs as $i => $q): $r = $ans[$q['id']] ?? null; $ok = $r && $r['is_correct']; $part = $r && !$ok && $r['points_earned'] > 0; ?>
<div class="card p-3 mb-2 border-start border-4 border-<?= $ok ? 'success' : ($part ? 'warning' : 'danger') ?>"><b><?= $i + 1 ?>. <?= e($q['question_text']) ?></b><div>Your answer: <?= answer_display($q, $r['answer_text'] ?? null) ?></div>
<?php if (!$ok): ?><div class="text-success small">Correct answer: <?= correct_display($q) ?></div><?php endif; ?><div class="small text-muted"><?= (float)($r['points_earned'] ?? 0) ?>/<?= (float)$q['points'] ?> pt<?= $q['explanation'] ? ' &middot; ' . e($q['explanation']) : '' ?></div></div><?php endforeach; endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
