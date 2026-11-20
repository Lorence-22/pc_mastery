<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin'); $id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT * FROM assembly_steps WHERE id=?'); $s->execute([$id]); $st = $s->fetch(); if (!$st) redirect('admin/assembly/index.php');
$zones = ['workspace', 'esd', 'case', 'cpu_socket', 'thermal', 'cpu_cooler', 'ram_slots', 'mb_tray', 'psu_bay', 'drive_bay', 'pcie_slot', 'fan_mounts', 'atx_power', 'sata_ports', 'front_panel', 'inspection', 'io_ports', 'boot'];
$back = 'admin/assembly/index.php?module=' . $st['module_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $act = $_POST['action'] ?? '';
    if ($act === 'step') { $ty = in_array($_POST['step_type'], ['safety', 'placement', 'cable', 'inspection', 'test'], true) ? $_POST['step_type'] : 'placement';
        db()->prepare('UPDATE assembly_steps SET title=?,instruction=?,hint=?,explanation=?,step_type=?,points=? WHERE id=?')->execute([trim($_POST['title']), trim($_POST['instruction']), trim($_POST['hint']), trim($_POST['explanation']), $ty, max(0.5, (float)$_POST['points']), $id]); flash('success', 'Step saved.'); }
    if ($act === 'delete_step') { db()->prepare('DELETE FROM assembly_steps WHERE id=?')->execute([$id]); db()->prepare('UPDATE assembly_steps SET step_no=step_no-1 WHERE module_id=? AND step_no>? ORDER BY step_no ASC')->execute([$st['module_id'], $st['step_no']]); flash('success', 'Step deleted.'); redirect($back); }
    if ($act === 'add_comp' || $act === 'edit_comp') {
        $label = trim($_POST['label'] ?? ''); $zone = $_POST['zone'] ?? 'none'; $isC = !empty($_POST['is_correct']) ? 1 : 0; if (!in_array($zone, array_merge($zones, ['none', 'later']), true)) $zone = 'none'; if ($isC && in_array($zone, ['none', 'later'], true)) { flash('error', 'A correct component needs a real target zone.'); redirect('admin/assembly/step.php?id=' . $id); }
        if ($label !== '') {
            if ($isC) db()->prepare('UPDATE assembly_components SET is_correct=0, target_zone=IF(target_zone IN ("none","later"),target_zone,"none") WHERE step_id=?' . ($act === 'edit_comp' ? ' AND id<>' . (int)$_POST['cid'] : ''))->execute([$id]);
            if ($act === 'add_comp') db()->prepare('INSERT INTO assembly_components (step_id,label,icon,is_correct,target_zone) VALUES (?,?,?,?,?)')->execute([$id, $label, trim($_POST['icon'] ?? '') ?: 'fa-cube', $isC, $zone]);
            else db()->prepare('UPDATE assembly_components SET label=?,icon=?,is_correct=?,target_zone=? WHERE id=? AND step_id=?')->execute([$label, trim($_POST['icon'] ?? '') ?: 'fa-cube', $isC, $zone, (int)$_POST['cid'], $id]);
            flash('success', 'Component saved.');
        }
    }
    if ($act === 'del_comp') { db()->prepare('DELETE FROM assembly_components WHERE id=? AND step_id=?')->execute([(int)$_POST['cid'], $id]); flash('success', 'Component deleted.'); }
    redirect('admin/assembly/step.php?id=' . $id);
}
$cs = db()->prepare('SELECT * FROM assembly_components WHERE step_id=? ORDER BY is_correct DESC, id'); $cs->execute([$id]); $comps = $cs->fetchAll();
$pageTitle = 'Step ' . $st['step_no'] . ': ' . $st['title']; $activeNav = 'assembly';
include __DIR__ . '/../../includes/header.php';
$zopt = function ($cur) use ($zones) { $h = ''; foreach (array_merge(['none', 'later'], $zones) as $z) $h .= '<option value="' . $z . '" ' . sel($cur, $z) . '>' . $z . ($z === 'none' ? ' (wrong choice)' : ($z === 'later' ? ' (belongs to a later step)' : '')) . '</option>'; return $h; };
?>
<a class="btn btn-outline-secondary mb-3" href="<?= url($back) ?>"><i class="fa fa-arrow-left"></i> Steps</a>
<div class="row g-3"><div class="col-lg-6"><div class="card p-3"><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="step">
<div class="col-md-8"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= e($st['title']) ?>" required></div><div class="col-md-4"><label class="form-label">Points</label><input type="number" step="0.5" name="points" class="form-control" value="<?= (float)$st['points'] ?>"></div>
<div class="col-12"><label class="form-label">Type (used in score breakdown)</label><select name="step_type" class="form-select"><?php foreach (['safety', 'placement', 'cable', 'inspection', 'test'] as $t) echo '<option ' . sel($st['step_type'], $t) . '>' . $t . '</option>'; ?></select></div>
<div class="col-12"><label class="form-label">Instruction</label><textarea name="instruction" rows="2" class="form-control" required><?= e($st['instruction']) ?></textarea></div><div class="col-12"><label class="form-label">Hint (practice only)</label><input name="hint" class="form-control" value="<?= e($st['hint']) ?>"></div>
<div class="col-12"><label class="form-label">Explanation (practice only)</label><textarea name="explanation" rows="2" class="form-control"><?= e($st['explanation']) ?></textarea></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save step</button></div></form>
<form method="post" class="mt-2"><?= csrf_field() ?><input type="hidden" name="action" value="delete_step"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this step and its components? Later steps are renumbered.">Delete step</button></form></div></div>
<div class="col-lg-6"><div class="card p-3"><h6>Components in the tray</h6><p class="small text-muted">Exactly one component must be correct, with a real target zone. Use "later" for a part that belongs to a later step (tests assembly sequence).</p>
<?php foreach ($comps as $c): ?><form method="post" class="row g-1 mb-2 border-bottom pb-2 align-items-center"><?= csrf_field() ?><input type="hidden" name="cid" value="<?= $c['id'] ?>"><div class="col-5"><input name="label" class="form-control form-control-sm" value="<?= e($c['label']) ?>"></div><div class="col-3"><input name="icon" class="form-control form-control-sm" value="<?= e($c['icon']) ?>"></div><div class="col-4"><select name="zone" class="form-select form-select-sm"><?= $zopt($c['target_zone']) ?></select></div>
<div class="col-12 d-flex gap-2 align-items-center"><label class="small"><input type="checkbox" name="is_correct" <?= $c['is_correct'] ? 'checked' : '' ?>> correct</label><button name="action" value="edit_comp" class="btn btn-sm btn-outline-primary">Save</button><button name="action" value="del_comp" class="btn btn-sm btn-outline-danger" data-confirm="Delete component?">Delete</button></div></form><?php endforeach; ?>
<form method="post" class="row g-1 mt-2"><?= csrf_field() ?><input type="hidden" name="action" value="add_comp"><div class="col-5"><input name="label" class="form-control form-control-sm" placeholder="New component label" required></div><div class="col-3"><input name="icon" class="form-control form-control-sm" placeholder="fa-cube"></div><div class="col-4"><select name="zone" class="form-select form-select-sm"><?= $zopt('none') ?></select></div><div class="col-12 d-flex gap-2"><label class="small"><input type="checkbox" name="is_correct"> correct</label><button class="btn btn-sm btn-success">Add</button></div></form></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
