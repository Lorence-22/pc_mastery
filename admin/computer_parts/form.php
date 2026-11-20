<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
$id = (int)($_GET['id'] ?? 0); $errors = [];
$keys = ['category_id', 'name', 'icon', 'description', 'function_text', 'purpose', 'specifications', 'install_location', 'install_instructions', 'related_parts'];
$d = array_fill_keys($keys, ''); $d['image'] = null; $d['icon'] = 'fa-microchip';
if ($id) { $s = db()->prepare('SELECT * FROM computer_parts WHERE id=?'); $s->execute([$id]); $d = $s->fetch() ?: redirect('admin/computer_parts/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); foreach ($keys as $k) $d[$k] = trim($_POST[$k] ?? '');
    if ($d['name'] === '') $errors[] = 'Name is required.'; if (!(int)$d['category_id']) $errors[] = 'Choose a category.';
    $img = $d['image'];
    if (!$errors && !empty($_FILES['image']['name'])) { try { $img = save_upload($_FILES['image'], 'profiles', IMAGE_TYPES, 2097152)['name']; } catch (RuntimeException $x) { $errors[] = $x->getMessage(); } }
    if (!$errors) {
        $slug = slugify($d['name']); $c = db()->prepare('SELECT COUNT(*) FROM computer_parts WHERE slug=? AND id<>?'); $c->execute([$slug, $id]); if ($c->fetchColumn()) $slug .= '-' . random_int(100, 999);
        $vals = [(int)$d['category_id'], $d['name'], $d['icon'] ?: 'fa-microchip', $img, $d['description'], $d['function_text'], $d['purpose'], $d['specifications'], $d['install_location'], $d['install_instructions'], $d['related_parts']];
        if ($id) { db()->prepare('UPDATE computer_parts SET category_id=?,name=?,icon=?,image=?,description=?,function_text=?,purpose=?,specifications=?,install_location=?,install_instructions=?,related_parts=? WHERE id=?')->execute([...$vals, $id]); }
        else { db()->prepare('INSERT INTO computer_parts (category_id,name,icon,image,description,function_text,purpose,specifications,install_location,install_instructions,related_parts,slug) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$vals, $slug]); }
        log_activity('part_saved', $d['name']); flash('success', 'Part saved.'); redirect('admin/computer_parts/index.php');
    }
}
$cats = db()->query('SELECT * FROM computer_categories ORDER BY name')->fetchAll();
$pageTitle = $id ? 'Edit Part' : 'Add Part'; $activeNav = 'parts';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4" style="max-width:900px"><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
<div class="col-md-5"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= e($d['name']) ?>" required></div>
<div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= sel($d['category_id'], $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Font Awesome icon</label><input name="icon" class="form-control" value="<?= e($d['icon']) ?>" placeholder="fa-microchip"></div>
<div class="col-12"><label class="form-label">Image (optional; icon is used if empty)</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
<?php foreach ([['description', 'Description'], ['function_text', 'Function'], ['purpose', 'Purpose'], ['specifications', 'Important specifications'], ['install_location', 'Installation location'], ['install_instructions', 'Installation instructions']] as [$n, $l]): ?>
<div class="col-md-6"><label class="form-label"><?= $l ?></label><textarea name="<?= $n ?>" rows="3" class="form-control"><?= e($d[$n]) ?></textarea></div><?php endforeach; ?>
<div class="col-12"><label class="form-label">Related components (comma-separated names)</label><input name="related_parts" class="form-control" value="<?= e($d['related_parts']) ?>"></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="index.php">Cancel</a></div></form></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
