<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student'); $id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT p.*, c.name cat FROM computer_parts p JOIN computer_categories c ON c.id=p.category_id WHERE p.id=?'); $s->execute([$id]); $p = $s->fetch();
if (!$p) { flash('error', 'Part not found.'); redirect('student/lessons/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { require_csrf(); mark_progress($user['id'], 'part', $id); log_activity('part_studied', $p['name']); flash('success', 'Marked as studied.'); redirect('student/lessons/view.php?id=' . $id); }
$studied = in_array($id, get_part_ids_done($user['id'], 'part'), true);
$related = [];
foreach (array_filter(array_map('trim', explode(',', (string)$p['related_parts']))) as $nm) { $r = db()->prepare('SELECT id,name FROM computer_parts WHERE name LIKE ? AND id<>? LIMIT 1'); $r->execute(["%$nm%", $id]); if ($row = $r->fetch()) $related[] = $row; else $related[] = ['id' => 0, 'name' => $nm]; }
$pageTitle = $p['name']; $activeNav = 'lessons';
include __DIR__ . '/../../includes/header.php';
?>
<a class="btn btn-outline-secondary mb-3" href="index.php"><i class="fa fa-arrow-left"></i> All parts</a>
<div class="row g-3"><div class="col-lg-4"><div class="card p-4 text-center"><div class="part-hero"><?php if ($p['image']): ?><img src="<?= url('uploads/profiles/' . e($p['image'])) ?>" style="max-width:100%;max-height:200px;border-radius:12px"><?php else: ?><i class="fa <?= e($p['icon'] ?: 'fa-microchip') ?>"></i><?php endif; ?></div>
<h4 class="mt-3"><?= e($p['name']) ?></h4><span class="badge text-bg-primary"><?= e($p['cat']) ?></span>
<form method="post" class="mt-3"><?= csrf_field() ?><?php if ($studied): ?><button class="btn btn-success w-100" disabled><i class="fa fa-check"></i> Studied</button><?php else: ?><button class="btn btn-primary w-100"><i class="fa fa-check"></i> Mark as studied</button><?php endif; ?></form></div></div>
<div class="col-lg-8"><div class="card p-4"><?php foreach ([['Description', 'description', 'fa-circle-info'], ['Function', 'function_text', 'fa-gears'], ['Purpose', 'purpose', 'fa-bullseye'], ['Important specifications', 'specifications', 'fa-list-check'], ['Installation location', 'install_location', 'fa-location-dot'], ['Installation instructions', 'install_instructions', 'fa-screwdriver-wrench']] as [$l, $k, $i]): ?>
<div class="mb-3"><h6><i class="fa <?= $i ?> text-primary me-1"></i><?= $l ?></h6><p class="mb-0"><?= nl2br(e($p[$k])) ?></p></div><?php endforeach; ?>
<h6><i class="fa fa-link text-primary me-1"></i>Related components</h6><?php foreach ($related as $r): ?><?= $r['id'] ? '<a class="badge text-bg-light text-decoration-none me-1" href="view.php?id=' . $r['id'] . '">' . e($r['name']) . '</a>' : '<span class="badge text-bg-light me-1">' . e($r['name']) . '</span>' ?><?php endforeach; ?></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
