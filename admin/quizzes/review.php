<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/quiz_lib.php';
$user = require_login('admin'); $aid = (int)($_GET['attempt'] ?? 0);
$s = db()->prepare("SELECT a.*, z.title, z.method, z.passing_score, z.id qid, CONCAT(sp.first_name,' ',sp.last_name) name FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id JOIN student_profiles sp ON sp.user_id=a.user_id WHERE a.id=?"); $s->execute([$aid]); $at = $s->fetch();
if (!$at || $at['status'] === 'in_progress') { flash('error', 'Attempt not found.'); redirect('admin/quizzes/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if ($at['method'] === 'pdf') {
        $pct = min(100, max(0, (float)($_POST['percentage'] ?? 0)));
        db()->prepare("UPDATE quiz_attempts SET score=?,total_points=100,percentage=?,passed=?,status='graded',admin_feedback=? WHERE id=?")->execute([$pct, $pct, $pct >= (float)$at['passing_score'] ? 1 : 0, trim($_POST['feedback'] ?? ''), $aid]);
    } else {
        $qs = load_questions((int)$at['qid']); $up = db()->prepare('UPDATE quiz_answers SET points_earned=?, is_correct=?, needs_review=0 WHERE attempt_id=? AND question_id=?');
        foreach ($qs as $q) { if (!isset($_POST['pts'][$q['id']])) continue; $p = min((float)$q['points'], max(0, (float)$_POST['pts'][$q['id']])); $up->execute([$p, $p >= (float)$q['points'] ? 1 : 0, $aid, $q['id']]); }
        recompute_attempt($aid); db()->prepare('UPDATE quiz_attempts SET admin_feedback=? WHERE id=?')->execute([trim($_POST['feedback'] ?? ''), $aid]);
    }
    notify((int)$at['user_id'], 'Your quiz "' . $at['title'] . '" has been graded.', url('student/quizzes/result.php?attempt=' . $aid));
    log_activity('quiz_graded', "Attempt #$aid"); flash('success', 'Grade saved.'); redirect('admin/quizzes/results.php?quiz=' . $at['qid']);
}
$pageTitle = 'Review: ' . $at['name']; $activeNav = 'quizzes';
include __DIR__ . '/../../includes/header.php';
?>
<a class="btn btn-outline-secondary mb-3" href="results.php?quiz=<?= $at['qid'] ?>"><i class="fa fa-arrow-left"></i> Results</a>
<form method="post" class="card p-4"><?= csrf_field() ?><h5><?= e($at['title']) ?> &middot; attempt #<?= $at['attempt_no'] ?> <small class="text-muted"><?= fmt_dt($at['submitted_at']) ?></small></h5>
<?php if ($at['method'] === 'pdf'): ?>
  <a class="btn btn-outline-primary mb-3" target="_blank" href="<?= url('api/file.php?t=quiz&id=' . $at['qid']) ?>"><i class="fa fa-file-pdf"></i> Open quiz PDF</a>
  <h6>Student's submitted answers</h6><pre class="border rounded p-3 bg-light" style="white-space:pre-wrap"><?= e($at['pdf_answer_text'] ?: '(empty)') ?></pre>
  <div class="row g-2"><div class="col-md-3"><label class="form-label">Score (%)</label><input type="number" name="percentage" min="0" max="100" step="0.01" class="form-control" value="<?= e($at['percentage'] ?? '') ?>" required></div></div>
<?php else: $qs = load_questions((int)$at['qid']); $m = db()->prepare('SELECT * FROM quiz_answers WHERE attempt_id=?'); $m->execute([$aid]); $ans = []; foreach ($m->fetchAll() as $r) $ans[$r['question_id']] = $r; ?>
  <?php foreach ($qs as $i => $q): $r = $ans[$q['id']] ?? null; ?><div class="border rounded p-3 mb-2 <?= !empty($r['needs_review']) ? 'border-warning' : '' ?>"><b><?= $i + 1 ?>. <?= e($q['question_text']) ?></b> <span class="badge text-bg-light"><?= QTYPES[$q['question_type']] ?></span>
    <div>Student: <?= answer_display($q, $r['answer_text'] ?? null) ?></div><div class="text-success small">Correct: <?= correct_display($q) ?></div>
    <div class="mt-1 d-flex align-items-center gap-2"><label class="small mb-0">Points (max <?= (float)$q['points'] ?>)</label><input type="number" step="0.25" min="0" max="<?= (float)$q['points'] ?>" name="pts[<?= $q['id'] ?>]" value="<?= (float)($r['points_earned'] ?? 0) ?>" class="form-control form-control-sm" style="width:90px"><?= !empty($r['needs_review']) ? '<span class="badge text-bg-warning">Check answer</span>' : '' ?></div></div><?php endforeach; ?>
<?php endif; ?>
<div class="mt-3"><label class="form-label">Feedback to student (optional)</label><textarea name="feedback" rows="2" class="form-control"><?= e($at['admin_feedback']) ?></textarea></div>
<div class="mt-3"><button class="btn btn-primary">Save grade</button></div></form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
