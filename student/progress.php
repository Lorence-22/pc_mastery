<?php
require_once __DIR__ . '/../includes/functions.php'; require_once __DIR__ . '/../includes/report_lib.php';
$user = require_login('student'); $R = student_report((int)$user['id']);
$parts = db()->query('SELECT id,name FROM computer_parts ORDER BY name')->fetchAll(); $topics = db()->query('SELECT id,title FROM reviewer_topics ORDER BY title')->fetchAll(); $mats = db()->query('SELECT id,title FROM learning_materials ORDER BY title')->fetchAll();
$dp = get_part_ids_done($user['id'], 'part'); $dt = get_part_ids_done($user['id'], 'topic'); $dm = get_part_ids_done($user['id'], 'material');
$pageTitle = 'Learning Progress'; $activeNav = 'progress';
include __DIR__ . '/../includes/header.php';
$list = function ($title, $items, $done, $col, $link) { echo '<div class="col-lg-4"><div class="card p-3 h-100"><h6>' . $title . ' <small class="text-muted">' . count(array_intersect(array_column($items, 'id'), $done)) . '/' . count($items) . '</small></h6>'; foreach ($items as $i) echo '<div class="d-flex justify-content-between py-1 border-bottom"><a href="' . e(url($link . $i['id'])) . '">' . e($i[$col]) . '</a><i class="fa fa-' . (in_array((int)$i['id'], $done, true) ? 'circle-check text-success' : 'circle text-muted') . '"></i></div>'; if (!$items) echo '<small class="text-muted">Nothing yet.</small>'; echo '</div></div>'; };
?>
<div class="card p-3 mb-3"><div class="d-flex justify-content-between"><strong>Overall learning progress</strong><span><?= $R['progress'] ?>% (<?= $R['progress_done'] ?>/<?= $R['progress_total'] ?>)</span></div><div class="progress mt-2" style="height:14px"><div class="progress-bar bg-info" style="width:<?= $R['progress'] ?>%"></div></div></div>
<div class="row g-3 mb-3"><?php $list('Computer Parts', $parts, $dp, 'name', 'student/lessons/view.php?id='); $list('Reviewer Topics', $topics, $dt, 'title', 'student/reviewers/topic.php?id='); $list('PDF / Image Materials', $mats, $dm, 'title', 'student/reviewers/material.php?id='); ?></div>
<div class="row g-3"><div class="col-md-6"><div class="card p-3 h-100"><h6 class="text-success">Strengths</h6><?php foreach ($R['strengths'] as $t) echo '<div class="d-flex justify-content-between"><span>' . e($t['topic']) . '</span><b>' . $t['pct'] . '%</b></div>'; if (!$R['strengths']) echo '<small class="text-muted">Take quizzes to see your strengths.</small>'; ?></div></div>
<div class="col-md-6"><div class="card p-3 h-100"><h6 class="text-danger">Review these topics</h6><?php foreach ($R['weaknesses'] as $t) echo '<div class="d-flex justify-content-between"><span>' . e($t['topic']) . '</span><b>' . $t['pct'] . '%</b></div>'; if (!$R['weaknesses']) echo '<small class="text-muted">No weak areas detected.</small>'; ?></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
