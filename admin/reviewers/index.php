<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $id = (int)$_POST['id'];
    if (($_POST['action'] ?? '') === 'del_topic') { db()->prepare('DELETE FROM reviewer_topics WHERE id=?')->execute([$id]); db()->prepare("DELETE FROM material_progress WHERE item_type='topic' AND item_id=?")->execute([$id]); log_activity('topic_deleted', "#$id"); flash('success', 'Topic deleted.'); }
    if (($_POST['action'] ?? '') === 'del_material') {
        $s = db()->prepare('SELECT file_name FROM learning_materials WHERE id=?'); $s->execute([$id]); if ($f = $s->fetchColumn()) @unlink(__DIR__ . '/../../uploads/reviewers/' . $f);
        db()->prepare('DELETE FROM learning_materials WHERE id=?')->execute([$id]); log_activity('material_deleted', "#$id"); flash('success', 'Material deleted.'); }
    redirect('admin/reviewers/index.php');
}
$topics = db()->query('SELECT t.*, z.title quiz FROM reviewer_topics t LEFT JOIN quizzes z ON z.id=t.related_quiz_id ORDER BY t.category,t.title')->fetchAll();
$mats = db()->query('SELECT m.*, t.title topic FROM learning_materials m LEFT JOIN reviewer_topics t ON t.id=m.topic_id ORDER BY m.created_at DESC')->fetchAll();
$pageTitle = 'Reviewer Management'; $activeNav = 'reviewers';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><div class="d-flex justify-content-between mb-2"><h6 class="mb-0">Reviewer Topics (text lessons)</h6><a class="btn btn-success btn-sm" href="topic_form.php"><i class="fa fa-plus"></i> Add Topic</a></div>
<div class="table-responsive"><table class="table align-middle table-sm"><thead><tr><th>Title</th><th>Category</th><th>Related quiz</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($topics as $t): ?><tr><td><?= e($t['title']) ?></td><td><?= e($t['category']) ?></td><td><?= e($t['quiz'] ?? '-') ?></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="topic_form.php?id=<?= $t['id'] ?>"><i class="fa fa-pen"></i></a>
<form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="del_topic"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this topic?"><i class="fa fa-trash"></i></button></form></td></tr>
<?php endforeach; if (!$topics) echo '<tr><td colspan="4" class="text-muted">No topics yet.</td></tr>'; ?></tbody></table></div></div>
<div class="card p-3"><div class="d-flex justify-content-between mb-2"><h6 class="mb-0">PDF Reviewers, Images and Diagrams</h6><a class="btn btn-success btn-sm" href="material_form.php"><i class="fa fa-upload"></i> Upload</a></div>
<div class="table-responsive"><table class="table align-middle table-sm"><thead><tr><th>Title</th><th>Type</th><th>Topic</th><th>Download</th><th>Uploaded</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($mats as $m): ?><tr><td><?= e($m['title']) ?><div class="small text-muted"><?= e($m['original_name']) ?></div></td><td><?= e(strtoupper($m['file_type'])) ?></td><td><?= e($m['topic'] ?? '-') ?></td><td><?= $m['downloadable'] ? 'Yes' : 'No' ?></td><td><?= fmt_dt($m['created_at']) ?></td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= url('api/file.php?t=material&id=' . $m['id']) ?>"><i class="fa fa-eye"></i></a> <a class="btn btn-sm btn-outline-secondary" href="material_form.php?id=<?= $m['id'] ?>"><i class="fa fa-pen"></i></a>
<form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="del_material"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this file?"><i class="fa fa-trash"></i></button></form></td></tr>
<?php endforeach; if (!$mats) echo '<tr><td colspan="6" class="text-muted">No files uploaded.</td></tr>'; ?></tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
