<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin'); $id = (int)($_GET['id'] ?? 0); $errors = [];
$d = ['title' => '', 'category' => '', 'content' => '', 'related_quiz_id' => ''];
if ($id) { $s = db()->prepare('SELECT * FROM reviewer_topics WHERE id=?'); $s->execute([$id]); $d = $s->fetch() ?: redirect('admin/reviewers/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); foreach (['title', 'category', 'content'] as $k) $d[$k] = trim($_POST[$k] ?? ''); $d['related_quiz_id'] = (int)($_POST['related_quiz_id'] ?? 0) ?: null;
    if ($d['title'] === '' || $d['category'] === '') $errors[] = 'Title and category are required.';
    if (!$errors) {
        if ($id) db()->prepare('UPDATE reviewer_topics SET title=?,category=?,content=?,related_quiz_id=? WHERE id=?')->execute([$d['title'], $d['category'], $d['content'], $d['related_quiz_id'], $id]);
        else db()->prepare('INSERT INTO reviewer_topics (title,category,content,related_quiz_id,created_by) VALUES (?,?,?,?,?)')->execute([$d['title'], $d['category'], $d['content'], $d['related_quiz_id'], $user['id']]);
        log_activity('topic_saved', $d['title']); flash('success', 'Topic saved.'); redirect('admin/reviewers/index.php');
    }
}
$quizzes = db()->query('SELECT id,title FROM quizzes ORDER BY title')->fetchAll();
$cats = ['Computer Hardware Lessons', 'Computer Component Identification', 'Functions of Computer Parts', 'Computer Hardware Specifications', 'Computer Assembly Procedures', 'Safety Precautions', 'Troubleshooting Basics'];
$pageTitle = $id ? 'Edit Topic' : 'Add Topic'; $activeNav = 'reviewers';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4" style="max-width:900px"><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="row g-3"><?= csrf_field() ?><div class="col-md-7"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= e($d['title']) ?>" required></div>
<div class="col-md-5"><label class="form-label">Category</label><input name="category" list="cl" class="form-control" value="<?= e($d['category']) ?>" required><datalist id="cl"><?php foreach ($cats as $c) echo '<option value="' . e($c) . '">'; ?></datalist></div>
<div class="col-12"><label class="form-label">Lesson content (plain text; blank line = new paragraph)</label><textarea name="content" rows="14" class="form-control"><?= e($d['content']) ?></textarea></div>
<div class="col-md-6"><label class="form-label">Related quiz (shown after reading)</label><select name="related_quiz_id" class="form-select"><option value="">None</option><?php foreach ($quizzes as $q): ?><option value="<?= $q['id'] ?>" <?= sel($d['related_quiz_id'], $q['id']) ?>><?= e($q['title']) ?></option><?php endforeach; ?></select></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="index.php">Cancel</a></div></form></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
