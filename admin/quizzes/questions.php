<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/quiz_lib.php';
$user = require_login('admin'); $quizId = (int)($_GET['quiz'] ?? 0);
$s = db()->prepare("SELECT * FROM quizzes WHERE id=? AND method='builder'"); $s->execute([$quizId]); $quiz = $s->fetch(); if (!$quiz) { flash('error', 'Quiz not found.'); redirect('admin/quizzes/index.php'); }
$errors = []; $editId = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $act = $_POST['action'] ?? 'save';
    if ($act === 'delete') { db()->prepare('DELETE FROM quiz_questions WHERE id=? AND quiz_id=?')->execute([(int)$_POST['id'], $quizId]); flash('success', 'Question deleted.'); redirect('admin/quizzes/questions.php?quiz=' . $quizId); }
    $type = $_POST['type'] ?? ''; $text = trim($_POST['text'] ?? ''); $pts = max(0.25, (float)($_POST['points'] ?? 1)); $topic = trim($_POST['topic'] ?? '') ?: null; $expl = trim($_POST['explanation'] ?? '') ?: null;
    $choices = []; $correctAns = null;
    if (!isset(QTYPES[$type])) $errors[] = 'Choose a question type.'; if ($text === '') $errors[] = 'Question text is required.';
    if ($type === 'multiple_choice' || $type === 'multiple_answer') {
        $texts = $_POST['mc_text'] ?? []; $corr = array_map('intval', $_POST['mc_correct'] ?? []);
        foreach ($texts as $i => $t) if (trim($t) !== '') $choices[] = ['t' => trim($t), 'm' => null, 'c' => in_array($i, $corr, true) ? 1 : 0];
        $nc = array_sum(array_column($choices, 'c'));
        if (count($choices) < 2) $errors[] = 'Provide at least two choices.'; elseif ($type === 'multiple_choice' && $nc !== 1) $errors[] = 'Mark exactly one correct choice.'; elseif ($type === 'multiple_answer' && $nc < 1) $errors[] = 'Mark at least one correct choice.';
    } elseif ($type === 'true_false') { $tf = $_POST['tf_correct'] ?? ''; if (!in_array($tf, ['true', 'false'], true)) $errors[] = 'Select True or False.'; $choices = [['t' => 'True', 'm' => null, 'c' => $tf === 'true' ? 1 : 0], ['t' => 'False', 'm' => null, 'c' => $tf === 'false' ? 1 : 0]];
    } elseif ($type === 'identification') { $correctAns = implode('|', array_filter(array_map('trim', explode('|', $_POST['ident'] ?? '')), 'strlen')); if ($correctAns === '') $errors[] = 'Enter at least one accepted answer.';
    } elseif ($type === 'enumeration') { $correctAns = implode('|', array_filter(array_map('trim', preg_split('/\R/', $_POST['enum'] ?? '')), 'strlen')); if ($correctAns === '') $errors[] = 'Enter the expected items, one per line.';
    } elseif ($type === 'matching') { foreach (($_POST['ml'] ?? []) as $i => $l) { $r = trim($_POST['mr'][$i] ?? ''); if (trim($l) !== '' && $r !== '') $choices[] = ['t' => trim($l), 'm' => $r, 'c' => 0]; } if (count($choices) < 2) $errors[] = 'Provide at least two matching pairs.'; }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            $eid = (int)($_POST['id'] ?? 0);
            if ($eid) { $pdo->prepare('UPDATE quiz_questions SET question_type=?,question_text=?,correct_answer=?,points=?,topic=?,explanation=? WHERE id=? AND quiz_id=?')->execute([$type, $text, $correctAns, $pts, $topic, $expl, $eid, $quizId]); $pdo->prepare('DELETE FROM quiz_choices WHERE question_id=?')->execute([$eid]); }
            else { $so = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM quiz_questions WHERE quiz_id=$quizId")->fetchColumn();
                $pdo->prepare('INSERT INTO quiz_questions (quiz_id,question_type,question_text,correct_answer,points,topic,explanation,sort_order) VALUES (?,?,?,?,?,?,?,?)')->execute([$quizId, $type, $text, $correctAns, $pts, $topic, $expl, $so]); $eid = (int)$pdo->lastInsertId(); }
            $ci = $pdo->prepare('INSERT INTO quiz_choices (question_id,choice_text,match_text,is_correct,sort_order) VALUES (?,?,?,?,?)'); foreach ($choices as $i => $c) $ci->execute([$eid, $c['t'], $c['m'], $c['c'], $i]);
            $pdo->commit(); log_activity('question_saved', "Quiz #$quizId"); flash('success', 'Question saved.'); redirect('admin/quizzes/questions.php?quiz=' . $quizId);
        } catch (PDOException $x) { $pdo->rollBack(); error_log($x->getMessage()); $errors[] = 'Database error.'; }
    }
}
$questions = load_questions($quizId);
$ed = null; foreach ($questions as $q) if ($q['id'] == $editId) $ed = $q;
$pageTitle = 'Questions: ' . $quiz['title']; $activeNav = 'quizzes';
include __DIR__ . '/../../includes/header.php';
$cur = fn($k, $dv = '') => e($ed[$k] ?? ($_POST[$k] ?? $dv));
$mc = $ed && in_array($ed['question_type'], ['multiple_choice', 'multiple_answer']) ? $ed['choices'] : [];
$ml = $ed && $ed['question_type'] === 'matching' ? $ed['choices'] : [];
?>
<a class="btn btn-outline-secondary mb-3" href="index.php"><i class="fa fa-arrow-left"></i> Quizzes</a>
<div class="row g-3"><div class="col-lg-7"><div class="card p-3"><h6><?= count($questions) ?> question(s) &middot; <?= array_sum(array_column($questions, 'points')) ?> points</h6>
<?php foreach ($questions as $i => $q): ?><div class="border rounded p-2 mb-2"><div class="d-flex justify-content-between"><div><span class="badge text-bg-light"><?= QTYPES[$q['question_type']] ?></span> <small class="text-muted"><?= (float)$q['points'] ?> pt<?= $q['topic'] ? ' &middot; ' . e($q['topic']) : '' ?></small><div><b><?= $i + 1 ?>.</b> <?= e($q['question_text']) ?></div><div class="small text-success">Answer: <?= correct_display($q) ?></div></div>
<div class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="?quiz=<?= $quizId ?>&edit=<?= $q['id'] ?>"><i class="fa fa-pen"></i></a><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $q['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete question?"><i class="fa fa-trash"></i></button></form></div></div></div>
<?php endforeach; if (!$questions) echo '<div class="empty-state"><i class="fa fa-list"></i><p>No questions yet. Add one on the right.</p></div>'; ?></div></div>
<div class="col-lg-5"><div class="card p-3"><h6><?= $ed ? 'Edit question' : 'Add question' ?></h6><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
<div class="col-12"><label class="form-label">Type</label><select name="type" id="qtype" class="form-select"><?php foreach (QTYPES as $k => $l): ?><option value="<?= $k ?>" <?= sel($ed['question_type'] ?? ($_POST['type'] ?? 'multiple_choice'), $k) ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Question</label><textarea name="text" rows="2" class="form-control" required><?= $cur('question_text') !== '' ? $cur('question_text') : e($_POST['text'] ?? '') ?></textarea></div>
<div class="col-6"><label class="form-label">Points</label><input type="number" step="0.25" min="0.25" name="points" class="form-control" value="<?= $cur('points', '1') ?>"></div><div class="col-6"><label class="form-label">Topic (for analytics)</label><input name="topic" class="form-control" value="<?= $cur('topic') ?>"></div>
<div class="col-12 qsec" data-t="multiple_choice multiple_answer"><label class="form-label">Choices (tick the correct one/ones)</label>
<?php for ($i = 0; $i < 6; $i++): ?><div class="input-group mb-1"><div class="input-group-text"><input type="checkbox" name="mc_correct[]" value="<?= $i ?>" <?= !empty($mc[$i]['is_correct']) ? 'checked' : '' ?>></div><input name="mc_text[<?= $i ?>]" class="form-control" placeholder="Choice <?= $i + 1 ?>" value="<?= e($mc[$i]['choice_text'] ?? '') ?>"></div><?php endfor; ?></div>
<div class="col-12 qsec" data-t="true_false"><label class="form-label">Correct answer</label><div><?php $tfc = $ed && $ed['question_type'] === 'true_false' ? ($ed['choices'][0]['is_correct'] ? 'true' : 'false') : ''; foreach (['true' => 'True', 'false' => 'False'] as $v => $l): ?><label class="me-3"><input type="radio" name="tf_correct" value="<?= $v ?>" <?= $tfc === $v ? 'checked' : '' ?>> <?= $l ?></label><?php endforeach; ?></div></div>
<div class="col-12 qsec" data-t="identification"><label class="form-label">Accepted answers (separate alternatives with |)</label><input name="ident" class="form-control" value="<?= e(($ed['question_type'] ?? '') === 'identification' ? $ed['correct_answer'] : '') ?>" placeholder="RAM|Random Access Memory"></div>
<div class="col-12 qsec" data-t="enumeration"><label class="form-label">Expected items (one per line; partial credit is given)</label><textarea name="enum" rows="4" class="form-control"><?= e(($ed['question_type'] ?? '') === 'enumeration' ? str_replace('|', "\n", $ed['correct_answer']) : '') ?></textarea></div>
<div class="col-12 qsec" data-t="matching"><label class="form-label">Pairs (left item / correct match)</label><?php for ($i = 0; $i < 6; $i++): ?><div class="input-group mb-1"><input name="ml[<?= $i ?>]" class="form-control" placeholder="Item" value="<?= e($ml[$i]['choice_text'] ?? '') ?>"><input name="mr[<?= $i ?>]" class="form-control" placeholder="Match" value="<?= e($ml[$i]['match_text'] ?? '') ?>"></div><?php endfor; ?></div>
<div class="col-12"><label class="form-label">Explanation (shown after submission)</label><input name="explanation" class="form-control" value="<?= $cur('explanation') ?>"></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save question</button><?php if ($ed): ?><a class="btn btn-outline-secondary" href="?quiz=<?= $quizId ?>">Cancel</a><?php endif; ?></div></form></div></div></div>
<script>const sel=document.getElementById('qtype');function sw(){document.querySelectorAll('.qsec').forEach(s=>{s.style.display=s.dataset.t.split(' ').includes(sel.value)?'':'none'})}sel.addEventListener('change',sw);sw();</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
