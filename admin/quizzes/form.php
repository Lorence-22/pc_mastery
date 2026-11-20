<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin'); $id = (int)($_GET['id'] ?? 0); $errors = [];
$d = ['title' => '', 'description' => '', 'category' => '', 'method' => 'builder', 'time_limit_minutes' => 30, 'passing_score' => 75, 'available_from' => '', 'available_until' => '', 'max_attempts' => 3, 'is_active' => 1, 'pdf_file' => null];
if ($id) { $s = db()->prepare('SELECT * FROM quizzes WHERE id=?'); $s->execute([$id]); $d = $s->fetch() ?: redirect('admin/quizzes/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['title', 'description', 'category'] as $k) $d[$k] = trim($_POST[$k] ?? '');
    if (!$id) $d['method'] = ($_POST['method'] ?? '') === 'pdf' ? 'pdf' : 'builder';
    $d['time_limit_minutes'] = max(1, (int)($_POST['time_limit_minutes'] ?? 30)); $d['passing_score'] = min(100, max(0, (float)($_POST['passing_score'] ?? 75))); $d['max_attempts'] = max(1, (int)($_POST['max_attempts'] ?? 1));
    $d['available_from'] = trim($_POST['available_from'] ?? '') ?: null; $d['available_until'] = trim($_POST['available_until'] ?? '') ?: null; $d['is_active'] = empty($_POST['is_active']) ? 0 : 1;
    if ($d['title'] === '' || $d['category'] === '') $errors[] = 'Title and category are required.';
    if ($d['available_from'] && $d['available_until'] && $d['available_until'] <= $d['available_from']) $errors[] = 'Closing date must be after the opening date.';
    $pdf = $d['pdf_file']; $has = !empty($_FILES['pdf']['name']);
    if ($d['method'] === 'pdf' && !$pdf && !$has) $errors[] = 'Please upload the quiz PDF.';
    if (!$errors && $has && $d['method'] === 'pdf') { try { $u = save_upload($_FILES['pdf'], 'pdf', PDF_TYPES, (int)setting('max_upload_mb', 10) * 1048576); if ($pdf) @unlink(__DIR__ . '/../../uploads/pdf/' . $pdf); $pdf = $u['name']; } catch (RuntimeException $x) { $errors[] = $x->getMessage(); } }
    if (!$errors) {
        $v = [$d['title'], $d['description'], $d['category'], $pdf, $d['time_limit_minutes'], $d['passing_score'], $d['available_from'], $d['available_until'], $d['max_attempts'], $d['is_active']];
        if ($id) db()->prepare('UPDATE quizzes SET title=?,description=?,category=?,pdf_file=?,time_limit_minutes=?,passing_score=?,available_from=?,available_until=?,max_attempts=?,is_active=? WHERE id=?')->execute([...$v, $id]);
        else { db()->prepare('INSERT INTO quizzes (title,description,category,pdf_file,time_limit_minutes,passing_score,available_from,available_until,max_attempts,is_active,method,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$v, $d['method'], $user['id']]); $id = (int)db()->lastInsertId(); }
        log_activity('quiz_saved', $d['title']); flash('success', 'Quiz saved.');
        redirect($d['method'] === 'builder' ? 'admin/quizzes/questions.php?quiz=' . $id : 'admin/quizzes/index.php');
    }
}
$dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
$pageTitle = $id ? 'Edit Quiz' : 'Create Quiz'; $activeNav = 'quizzes';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4" style="max-width:900px"><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
<div class="col-md-8"><label class="form-label">Quiz title</label><input name="title" class="form-control" value="<?= e($d['title']) ?>" required></div>
<div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="<?= e($d['category']) ?>" placeholder="e.g. Hardware Basics" required></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control"><?= e($d['description']) ?></textarea></div>
<div class="col-md-6"><label class="form-label">Creation method</label><select name="method" id="method" class="form-select" <?= $id ? 'disabled' : '' ?>><option value="builder" <?= sel($d['method'], 'builder') ?>>Interactive Quiz Builder (auto-graded)</option><option value="pdf" <?= sel($d['method'], 'pdf') ?>>PDF Upload (admin-graded)</option></select></div>
<div class="col-md-6" id="pdfBox"><label class="form-label">Quiz PDF <?= $d['pdf_file'] ? '(leave empty to keep current)' : '' ?></label><input type="file" name="pdf" class="form-control" accept=".pdf,application/pdf"></div>
<div class="col-md-3"><label class="form-label">Time limit (min)</label><input type="number" name="time_limit_minutes" min="1" class="form-control" value="<?= (int)$d['time_limit_minutes'] ?>"></div>
<div class="col-md-3"><label class="form-label">Passing score (%)</label><input type="number" step="0.01" min="0" max="100" name="passing_score" class="form-control" value="<?= (float)$d['passing_score'] ?>"></div>
<div class="col-md-3"><label class="form-label">Max attempts</label><input type="number" min="1" name="max_attempts" class="form-control" value="<?= (int)$d['max_attempts'] ?>"></div>
<div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" id="act" name="is_active" <?= $d['is_active'] ? 'checked' : '' ?>><label for="act" class="form-check-label">Active</label></div></div>
<div class="col-md-6"><label class="form-label">Available from</label><input type="datetime-local" name="available_from" class="form-control" value="<?= e($dt($d['available_from'])) ?>"></div>
<div class="col-md-6"><label class="form-label">Available until</label><input type="datetime-local" name="available_until" class="form-control" value="<?= e($dt($d['available_until'])) ?>"></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="index.php">Cancel</a></div></form></div>
<script>const m=document.getElementById('method'),b=document.getElementById('pdfBox');const t=()=>b.style.display=m.value==='pdf'?'':'none';m.addEventListener('change',t);t();</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
