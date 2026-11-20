<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student');
$w = ['1=1']; $p = [];
if (($qs = trim($_GET['q'] ?? '')) !== '') { $w[] = '(p.name LIKE ? OR p.description LIKE ? OR p.function_text LIKE ?)'; array_push($p, "%$qs%", "%$qs%", "%$qs%"); }
if (($cat = (int)($_GET['cat'] ?? 0)) > 0) { $w[] = 'p.category_id=?'; $p[] = $cat; }
$s = db()->prepare('SELECT p.*, c.name cat FROM computer_parts p JOIN computer_categories c ON c.id=p.category_id WHERE ' . implode(' AND ', $w) . ' ORDER BY p.name'); $s->execute($p); $rows = $s->fetchAll();
$cats = db()->query('SELECT * FROM computer_categories ORDER BY name')->fetchAll(); $done = get_part_ids_done($user['id'], 'part');
$pageTitle = 'Computer Parts Lessons'; $activeNav = 'lessons';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-5"><input name="q" class="form-control" placeholder="Search parts" value="<?= e($qs) ?>"></div>
<div class="col-md-4"><select name="cat" class="form-select"><option value="">All categories</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= sel($cat, $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3 d-flex gap-1"><button class="btn btn-primary flex-fill">Search</button><a class="btn btn-info text-white" href="game.php"><i class="fa fa-gamepad"></i> Identification Game</a></div></form></div>
<div class="row g-3"><?php foreach ($rows as $r): ?><div class="col-sm-6 col-xl-3"><a href="view.php?id=<?= $r['id'] ?>" class="card p-3 h-100 text-decoration-none text-dark part-card">
<div class="d-flex justify-content-between"><span class="part-ico"><?php if ($r['image']): ?><img src="<?= url('uploads/profiles/' . e($r['image'])) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px"><?php else: ?><i class="fa <?= e($r['icon'] ?: 'fa-microchip') ?>"></i><?php endif; ?></span><?php if (in_array((int)$r['id'], $done, true)): ?><span class="badge text-bg-success align-self-start">Studied</span><?php endif; ?></div>
<h6 class="mt-3 mb-1"><?= e($r['name']) ?></h6><small class="text-muted d-block mb-2"><?= e($r['cat']) ?></small><small><?= e(mb_strimwidth($r['description'], 0, 90, '...')) ?></small></a></div>
<?php endforeach; if (!$rows): ?><div class="col-12"><div class="empty-state"><i class="fa fa-magnifying-glass"></i><p>No parts match your search.</p></div></div><?php endif; ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
