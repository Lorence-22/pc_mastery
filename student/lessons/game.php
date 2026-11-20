<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student');
$parts = db()->query('SELECT id,name,icon,image,function_text FROM computer_parts ORDER BY id')->fetchAll();
foreach ($parts as &$p) $p['image'] = $p['image'] ? url('uploads/profiles/' . $p['image']) : null; unset($p);
$pageTitle = 'Parts Identification Game'; $activeNav = 'lessons';
$extraScripts = '<script>window.PARTS=' . json_encode($parts) . ';</script><script src="' . url('assets/js/parts_game.js') . '"></script>';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4 mx-auto text-center" style="max-width:640px"><div class="d-flex justify-content-between mb-2"><span>Question <b id="qn">1</b>/<span id="qt">10</span></span><span>Score <b id="sc">0</b></span></div>
<div class="progress mb-3"><div id="bar" class="progress-bar bg-info" style="width:0"></div></div>
<div id="pic" class="part-hero mb-2"></div><p class="text-muted" id="clue"></p><h5>Which computer component is this?</h5><div id="opts" class="d-grid gap-2 mt-3"></div><div id="fb" class="mt-3"></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
