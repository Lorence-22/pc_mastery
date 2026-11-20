<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/quiz_lib.php';
$user = require_login('student'); $uid = $user['id']; $qid = (int)($_GET['quiz'] ?? 0);
$s = db()->prepare('SELECT * FROM quizzes WHERE id=?'); $s->execute([$qid]); $quiz = $s->fetch(); if (!$quiz) { flash('error', 'Quiz not found.'); redirect('student/quizzes/index.php'); }
$a = db()->prepare("SELECT * FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status='in_progress' LIMIT 1"); $a->execute([$qid, $uid]); $at = $a->fetch();
if (!$at) {
    if ($m = quiz_open($quiz)) { flash('error', $m); redirect('student/quizzes/index.php'); }
    $c = db()->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status<>'in_progress'"); $c->execute([$qid, $uid]); $used = (int)$c->fetchColumn();
    if ($used >= $quiz['max_attempts']) { flash('error', 'No attempts left for this quiz.'); redirect('student/quizzes/index.php'); }
    if ($quiz['method'] === 'builder' && !load_questions($qid)) { flash('error', 'This quiz has no questions yet.'); redirect('student/quizzes/index.php'); }
    db()->prepare('INSERT INTO quiz_attempts (quiz_id,user_id,attempt_no,started_at) VALUES (?,?,?,NOW())')->execute([$qid, $uid, $used + 1]);
    $a->execute([$qid, $uid]); $at = $a->fetch(); log_activity('quiz_started', $quiz['title']);
}
$deadline = strtotime($at['started_at']) + $quiz['time_limit_minutes'] * 60; $remaining = $deadline - time();
$questions = $quiz['method'] === 'builder' ? load_questions($qid) : [];
function submit_now(array $quiz, array $at, array $questions): never {
    finalize_attempt((int)$at['id']); log_activity('quiz_submitted', $quiz['title']); redirect('student/quizzes/result.php?attempt=' . $at['id']);
}
if ($remaining < -30) { submit_now($quiz, $at, $questions); }   // expired long ago: grade what was autosaved
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if ($quiz['method'] === 'builder') save_posted_answers((int)$at['id'], $questions, $_POST['q'] ?? []);
    else db()->prepare('UPDATE quiz_attempts SET pdf_answer_text=? WHERE id=?')->execute([mb_substr(trim($_POST['pdf_answers'] ?? ''), 0, 20000), $at['id']]);
    submit_now($quiz, $at, $questions);
}
$saved = []; if ($quiz['method'] === 'builder') { $x = db()->prepare('SELECT question_id, answer_text FROM quiz_answers WHERE attempt_id=?'); $x->execute([$at['id']]); $saved = $x->fetchAll(PDO::FETCH_KEY_PAIR); }
$pageTitle = $quiz['title']; $activeNav = 'quizzes';
$extraScripts = '<script>window.QUIZ={attempt:' . (int)$at['id'] . ',remaining:' . max(0, $remaining) . ',method:' . json_encode($quiz['method']) . '};</script><script src="' . url('assets/js/quiz.js') . '"></script>';
include __DIR__ . '/../../includes/header.php';
?>
<div class="quiz-bar card p-3 mb-3 d-flex flex-row justify-content-between align-items-center sticky-top" style="top:70px;z-index:100"><div><b><?= e($quiz['title']) ?></b> <small class="text-muted">attempt #<?= $at['attempt_no'] ?></small> <span id="saveState" class="small text-muted ms-2"></span></div><div class="fs-5 fw-bold text-danger"><i class="fa fa-clock"></i> <span id="timer">--:--</span></div></div>
<form method="post" id="quizForm"><?= csrf_field() ?>
<?php if ($quiz['method'] === 'pdf'): ?>
<div class="row g-3"><div class="col-lg-7"><div class="card p-2"><iframe src="<?= url('api/file.php?t=quiz&id=' . $qid) ?>" style="width:100%;height:78vh;border:0"></iframe></div></div>
<div class="col-lg-5"><div class="card p-3"><h6>Your answers</h6><p class="small text-muted">Type your answers by item number (e.g. "1. B"). An instructor will grade them.</p><textarea name="pdf_answers" id="pdfAnswers" rows="18" class="form-control"><?= e($at['pdf_answer_text']) ?></textarea></div></div></div>
<?php else: foreach ($questions as $i => $q): $sv = $saved[$q['id']] ?? null; $svj = $sv !== null && $sv !== '' && $sv[0] === '[' || ($sv !== null && $sv[0] === '{') ? json_decode($sv, true) : null; $id = $q['id']; ?>
<div class="card p-3 mb-3"><div class="d-flex justify-content-between"><b>Question <?= $i + 1 ?></b><span class="badge text-bg-light"><?= (float)$q['points'] ?> pt</span></div><p class="mt-2"><?= nl2br(e($q['question_text'])) ?></p>
<?php switch ($q['question_type']):
  case 'multiple_choice': case 'true_false': foreach ($q['choices'] as $c): ?><div class="form-check"><input class="form-check-input" type="radio" name="q[<?= $id ?>]" id="c<?= $c['id'] ?>" value="<?= $c['id'] ?>" <?= (string)$sv === (string)$c['id'] ? 'checked' : '' ?>><label class="form-check-label" for="c<?= $c['id'] ?>"><?= e($c['choice_text']) ?></label></div><?php endforeach; break;
  case 'multiple_answer': echo '<div class="small text-muted mb-1">Select all that apply.</div>'; foreach ($q['choices'] as $c): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="q[<?= $id ?>][]" id="c<?= $c['id'] ?>" value="<?= $c['id'] ?>" <?= is_array($svj) && in_array((int)$c['id'], $svj, true) ? 'checked' : '' ?>><label class="form-check-label" for="c<?= $c['id'] ?>"><?= e($c['choice_text']) ?></label></div><?php endforeach; break;
  case 'identification': ?><input name="q[<?= $id ?>]" class="form-control" autocomplete="off" value="<?= e($sv) ?>" placeholder="Your answer"><?php break;
  case 'enumeration': ?><textarea name="q[<?= $id ?>]" rows="4" class="form-control" placeholder="One item per line"><?= is_array($svj) ? e(implode("\n", $svj)) : '' ?></textarea><?php break;
  case 'matching': $opts = array_column($q['choices'], 'match_text'); sort($opts); foreach ($q['choices'] as $c): ?><div class="row g-2 mb-1 align-items-center"><div class="col-md-5"><?= e($c['choice_text']) ?></div><div class="col-md-7"><select name="q[<?= $id ?>][<?= $c['id'] ?>]" class="form-select form-select-sm"><option value="">-- choose --</option><?php foreach ($opts as $o): ?><option <?= is_array($svj) && ($svj[$c['id']] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></div></div><?php endforeach; break;
endswitch; ?></div>
<?php endforeach; endif; ?>
<button type="button" class="btn btn-success btn-lg" id="submitBtn"><i class="fa fa-paper-plane"></i> Submit</button></form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
