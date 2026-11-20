<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $id = (int)$_POST['id'];
    if (($_POST['action'] ?? '') === 'toggle') { db()->prepare('UPDATE quizzes SET is_active=1-is_active WHERE id=?')->execute([$id]); flash('success', 'Quiz status changed.'); }
    if (($_POST['action'] ?? '') === 'delete') { $s = db()->prepare('SELECT pdf_file FROM quizzes WHERE id=?'); $s->execute([$id]); if ($f = $s->fetchColumn()) @unlink(__DIR__ . '/../../uploads/pdf/' . $f);
        db()->prepare('DELETE FROM quizzes WHERE id=?')->execute([$id]); log_activity('quiz_deleted', "#$id"); flash('success', 'Quiz deleted.'); }
    redirect('admin/quizzes/index.php');
}
$qs = trim($_GET['q'] ?? ''); $w = ['1=1']; $p = []; if ($qs !== '') { $w[] = '(z.title LIKE ? OR z.category LIKE ?)'; array_push($p, "%$qs%", "%$qs%"); }
$pg = paginate('SELECT COUNT(*) FROM quizzes z WHERE ' . implode(' AND ', $w), $p);
$s = db()->prepare('SELECT z.*, (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id=z.id) nq, (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=z.id AND status<>"in_progress") na,
 (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=z.id AND status="needs_review") pend FROM quizzes z WHERE ' . implode(' AND ', $w) . " ORDER BY z.created_at DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}"); $s->execute($p); $rows = $s->fetchAll();
$pageTitle = 'Quiz Management'; $activeNav = 'quizzes';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-8"><input name="q" class="form-control" placeholder="Search quizzes" value="<?= e($qs) ?>"></div><div class="col-md-4 d-flex gap-1"><button class="btn btn-primary flex-fill">Search</button><a class="btn btn-success" href="form.php"><i class="fa fa-plus"></i> New Quiz</a></div></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>Method</th><th>Settings</th><th>Attempts</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($r['title']) ?></b><div class="small text-muted"><?= e($r['category']) ?></div></td>
<td><span class="badge text-bg-<?= $r['method'] === 'pdf' ? 'warning' : 'info' ?>"><?= $r['method'] === 'pdf' ? 'PDF' : 'Builder (' . $r['nq'] . ' Q)' ?></span></td>
<td class="small"><?= $r['time_limit_minutes'] ?> min &middot; pass <?= (float)$r['passing_score'] ?>% &middot; <?= $r['max_attempts'] ?> tries</td>
<td><?= $r['na'] ?> <?= $r['pend'] ? '<span class="badge text-bg-danger">' . $r['pend'] . ' to review</span>' : '' ?></td>
<td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-<?= $r['is_active'] ? 'success' : 'secondary' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></button></form></td>
<td class="text-end text-nowrap"><?php if ($r['method'] === 'builder'): ?><a class="btn btn-sm btn-outline-info" href="questions.php?quiz=<?= $r['id'] ?>" title="Questions"><i class="fa fa-list"></i></a> <?php else: ?><a class="btn btn-sm btn-outline-info" target="_blank" href="<?= url('api/file.php?t=quiz&id=' . $r['id']) ?>" title="View PDF"><i class="fa fa-file-pdf"></i></a> <?php endif; ?>
<a class="btn btn-sm btn-outline-primary" href="results.php?quiz=<?= $r['id'] ?>" title="Results"><i class="fa fa-square-poll-vertical"></i></a> <a class="btn btn-sm btn-outline-secondary" href="form.php?id=<?= $r['id'] ?>"><i class="fa fa-pen"></i></a>
<form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this quiz and all attempts?"><i class="fa fa-trash"></i></button></form></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="6"><div class="empty-state"><i class="fa fa-circle-question"></i><p>No quizzes yet.</p></div></td></tr>'; ?></tbody></table></div><?= pager($pg) ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
