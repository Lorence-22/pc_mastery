<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin'); $id = (int)($_GET['id'] ?? 0); $errors = [];
$d = ['title' => '', 'description' => '', 'topic_id' => '', 'downloadable' => 1];
if ($id) { $s = db()->prepare('SELECT * FROM learning_materials WHERE id=?'); $s->execute([$id]); $d = $s->fetch() ?: redirect('admin/reviewers/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $d['title'] = trim($_POST['title'] ?? ''); $d['description'] = trim($_POST['description'] ?? ''); $d['topic_id'] = (int)($_POST['topic_id'] ?? 0) ?: null; $d['downloadable'] = empty($_POST['downloadable']) ? 0 : 1;
    if ($d['title'] === '') $errors[] = 'Title is required.';
    $up = null; $has = !empty($_FILES['file']['name']);
    if (!$id && !$has) $errors[] = 'Please choose a file.';
    if (!$errors && $has) { try { $up = save_upload($_FILES['file'], 'reviewers', PDF_TYPES + IMAGE_TYPES, (int)setting('max_upload_mb', 10) * 1048576); } catch (RuntimeException $x) { $errors[] = $x->getMessage(); } }
    if (!$errors) {
        if ($id) { db()->prepare('UPDATE learning_materials SET title=?,description=?,topic_id=?,downloadable=? WHERE id=?')->execute([$d['title'], $d['description'], $d['topic_id'], $d['downloadable'], $id]);
            if ($up) { @unlink(__DIR__ . '/../../uploads/reviewers/' . $d['file_name']); db()->prepare('UPDATE learning_materials SET file_name=?,original_name=?,file_type=? WHERE id=?')->execute([$up['name'], $up['original'], $up['ext'] === 'pdf' ? 'pdf' : 'image', $id]); } }
        else db()->prepare('INSERT INTO learning_materials (title,description,topic_id,file_name,original_name,file_type,downloadable,uploaded_by) VALUES (?,?,?,?,?,?,?,?)')->execute([$d['title'], $d['description'], $d['topic_id'], $up['name'], $up['original'], $up['ext'] === 'pdf' ? 'pdf' : 'image', $d['downloadable'], $user['id']]);
        log_activity('material_saved', $d['title']); flash('success', 'Material saved.'); redirect('admin/reviewers/index.php');
    }
}
$topics = db()->query('SELECT id,title FROM reviewer_topics ORDER BY title')->fetchAll();
$pageTitle = $id ? 'Edit Material' : 'Upload Material'; $activeNav = 'reviewers';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4" style="max-width:760px"><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?><div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= e($d['title']) ?>" required></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control"><?= e($d['description']) ?></textarea></div>
<div class="col-md-6"><label class="form-label">Topic</label><select name="topic_id" class="form-select"><option value="">None</option><?php foreach ($topics as $t): ?><option value="<?= $t['id'] ?>" <?= sel($d['topic_id'], $t['id']) ?>><?= e($t['title']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">File (PDF, JPG, PNG, WEBP; max <?= (int)setting('max_upload_mb', 10) ?> MB) <?= $id ? '- leave empty to keep' : '' ?></label><input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="downloadable" id="dl" <?= $d['downloadable'] ? 'checked' : '' ?>><label class="form-check-label" for="dl">Students may download this file</label></div></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="index.php">Cancel</a></div></form></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
