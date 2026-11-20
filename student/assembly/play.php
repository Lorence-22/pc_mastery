<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('student');
$mode = ($_GET['mode'] ?? '') === 'assessment' ? 'assessment' : 'practice'; $mid = (int)($_GET['module'] ?? 0);
$m = db()->prepare('SELECT * FROM assembly_modules WHERE id=? AND is_active=1'); $m->execute([$mid]); $mod = $m->fetch(); if (!$mod) { flash('error', 'Module not found.'); redirect('student/assembly/index.php'); }
$pageTitle = ($mode === 'assessment' ? 'Assembly Assessment' : 'Assembly Practice') . ': ' . $mod['title']; $activeNav = 'assembly';
$extraScripts = '<script>window.ASM={module:' . $mid . ',mode:' . json_encode($mode) . ',resultUrl:' . json_encode(url('student/assembly/result.php?attempt=')) . ',certUrl:' . json_encode(url('student/certificates/view.php?id=')) . '};</script><script src="' . url('assets/js/assembly.js') . '"></script>';
include __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3"><div class="col-xl-3 order-xl-1"><div class="card p-3 mb-3"><div class="d-flex justify-content-between"><span class="badge text-bg-<?= $mode === 'assessment' ? 'danger' : 'info' ?>"><?= $mode === 'assessment' ? 'ASSESSMENT MODE' : 'PRACTICE MODE' ?></span><small>Step <b id="stepNo">1</b>/<span id="stepTotal">18</span></small></div>
<div class="progress my-2" style="height:8px"><div class="progress-bar bg-success" id="pbar" style="width:0"></div></div>
<h6 id="stepTitle" class="mt-2">Loading...</h6><p id="stepIns" class="small mb-2"></p><span class="badge text-bg-light" id="stepType"></span>
<div id="hintBox" class="alert alert-warning py-2 small mt-2 d-none"></div>
<div class="d-flex gap-2 mt-2"><?php if ($mode === 'practice'): ?><button class="btn btn-sm btn-warning" id="hintBtn"><i class="fa fa-lightbulb"></i> Hint</button><?php endif; ?><button class="btn btn-sm btn-outline-danger" id="restartBtn"><i class="fa fa-rotate-left"></i> Restart</button></div>
<div id="feedback" class="mt-3"></div></div>
<div class="card p-3 d-none d-xl-block"><h6>Steps</h6><ol id="stepList" class="small ps-3 mb-0"></ol></div></div>
<div class="col-xl-9 order-xl-2"><div class="card p-3 mb-3"><h6><i class="fa fa-hand"></i> Components <small class="text-muted">- drag onto the board (or tap, then tap a location)</small></h6><div id="tray" class="tray"></div></div>
<div class="card p-3"><h6><i class="fa fa-computer"></i> Assembly Board <small class="text-muted"><?= $mode === 'practice' ? '- zones are labeled' : '- zone labels are hidden; identify locations yourself' ?></small></h6><div id="stage" class="stage"></div></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
