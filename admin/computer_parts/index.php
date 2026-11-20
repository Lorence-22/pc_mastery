<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_csrf(); $id = (int)$_POST['id'];
    db()->prepare('DELETE FROM computer_parts WHERE id=?')->execute([$id]); log_activity('part_deleted', "Part #$id"); flash('success', 'Part deleted.'); redirect('admin/computer_parts/index.php');
}
$w = ['1=1']; $p = [];
if (($qs = trim($_GET['q'] ?? '')) !== '') { $w[] = '(p.name LIKE ? OR p.description LIKE ?)'; array_push($p, "%$qs%", "%$qs%"); }
if (($cat = (int)($_GET['cat'] ?? 0)) > 0) { $w[] = 'p.category_id=?'; $p[] = $cat; }
$where = implode(' AND ', $w);
$pg = paginate("SELECT COUNT(*) FROM computer_parts p WHERE $where", $p);
$s = db()->prepare("SELECT p.*, c.name cat FROM computer_parts p JOIN computer_categories c ON c.id=p.category_id WHERE $where ORDER BY p.name LIMIT {$pg['per']} OFFSET {$pg['offset']}"); $s->execute($p); $rows = $s->fetchAll();
$cats = db()->query('SELECT * FROM computer_categories ORDER BY name')->fetchAll();
$pageTitle = 'Computer Parts'; $activeNav = 'parts';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-5"><input name="q" class="form-control" placeholder="Search parts" value="<?= e($qs) ?>"></div>
<div class="col-md-4"><select name="cat" class="form-select"><option value="">All categories</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= sel($cat, $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3 d-flex gap-1"><button class="btn btn-primary flex-fill">Filter</button><a class="btn btn-success" href="form.php"><i class="fa fa-plus"></i> Add</a><a class="btn btn-outline-secondary" href="categories.php">Categories</a></div></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th></th><th>Name</th><th>Category</th><th>Location</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td style="width:56px"><?php if ($r['image']): ?><img src="<?= url('uploads/profiles/' . e($r['image'])) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px"><?php else: ?><span class="rec-item p-0"><i class="fa <?= e($r['icon'] ?: 'fa-microchip') ?>"></i></span><?php endif; ?></td>
<td><b><?= e($r['name']) ?></b><div class="small text-muted"><?= e(mb_strimwidth($r['description'], 0, 70, '...')) ?></div></td><td><?= e($r['cat']) ?></td><td class="small"><?= e(mb_strimwidth($r['install_location'], 0, 50, '...')) ?></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="form.php?id=<?= $r['id'] ?>"><i class="fa fa-pen"></i></a>
<form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this part?"><i class="fa fa-trash"></i></button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5"><div class="empty-state"><i class="fa fa-microchip"></i><p>No parts found.</p></div></td></tr><?php endif; ?></tbody></table></div><?= pager($pg) ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
