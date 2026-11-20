<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student');
$qs = trim($_GET['q'] ?? ''); $cat = trim($_GET['cat'] ?? '');
$w = ['1=1']; $p = []; if ($qs !== '') { $w[] = '(t.title LIKE ? OR t.content LIKE ?)'; array_push($p, "%$qs%", "%$qs%"); } if ($cat !== '') { $w[] = 't.category=?'; $p[] = $cat; }
$s = db()->prepare('SELECT t.* FROM reviewer_topics t WHERE ' . implode(' AND ', $w) . ' ORDER BY t.category,t.title'); $s->execute($p); $topics = $s->fetchAll();
$w = ['1=1']; $p = []; if ($qs !== '') { $w[] = '(title LIKE ? OR description LIKE ?)'; array_push($p, "%$qs%", "%$qs%"); }
$s = db()->prepare('SELECT * FROM learning_materials WHERE ' . implode(' AND ', $w) . ' ORDER BY created_at DESC'); $s->execute($p); $mats = $s->fetchAll();
$cats = db()->query('SELECT DISTINCT category FROM reviewer_topics ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
$dt = get_part_ids_done($user['id'], 'topic'); $dm = get_part_ids_done($user['id'], 'material');
$pageTitle = 'Reviewer'; $activeNav = 'reviewers';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-6"><input name="q" class="form-control" placeholder="Search reviewers" value="<?= e($qs) ?>"></div><div class="col-md-4"><select name="cat" class="form-select"><option value="">All categories</option><?php foreach ($cats as $c): ?><option <?= sel($cat, $c) ?>><?= e($c) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div></form></div>
<div class="row g-3"><div class="col-lg-7"><div class="card p-3"><h6>Lessons</h6><?php $last = ''; foreach ($topics as $t): if ($t['category'] !== $last) { echo '<div class="small text-uppercase text-muted mt-2">' . e($t['category']) . '</div>'; $last = $t['category']; } ?>
<a class="rec-item" href="topic.php?id=<?= $t['id'] ?>"><i class="fa fa-book"></i><div class="flex-fill"><?= e($t['title']) ?></div><?= in_array((int)$t['id'], $dt, true) ? '<span class="badge text-bg-success">Done</span>' : '' ?></a><?php endforeach; if (!$topics) echo '<div class="empty-state"><i class="fa fa-book"></i><p>No lessons found.</p></div>'; ?></div></div>
<div class="col-lg-5"><div class="card p-3"><h6>PDF &amp; Image Materials</h6><?php foreach ($mats as $m): ?><a class="rec-item" href="material.php?id=<?= $m['id'] ?>"><i class="fa fa-<?= $m['file_type'] === 'pdf' ? 'file-pdf' : 'image' ?>"></i><div class="flex-fill"><?= e($m['title']) ?></div><?= in_array((int)$m['id'], $dm, true) ? '<span class="badge text-bg-success">Done</span>' : '' ?></a><?php endforeach; if (!$mats) echo '<div class="empty-state"><i class="fa fa-file"></i><p>No files yet.</p></div>'; ?></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
