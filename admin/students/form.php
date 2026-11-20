<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
$id = (int)($_GET['id'] ?? 0); $errors = [];
$cur = null;
if ($id) { $s = db()->prepare('SELECT u.id,u.username,u.email,u.status,sp.* FROM users u JOIN student_profiles sp ON sp.user_id=u.id WHERE u.id=?'); $s->execute([$id]); $cur = $s->fetch(); if (!$cur) redirect('admin/students/index.php'); }
$d = $cur ?: ['student_id' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => '', 'username' => '', 'course' => '', 'year_level' => '', 'section' => '', 'status' => 'active', 'profile_picture' => null];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['student_id', 'first_name', 'middle_name', 'last_name', 'email', 'username', 'course', 'year_level', 'section'] as $k) $d[$k] = trim($_POST[$k] ?? '');
    $d['status'] = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active'; $pw = $_POST['password'] ?? '';
    foreach (['student_id', 'first_name', 'last_name', 'email', 'username', 'course', 'year_level', 'section'] as $k) if ($d[$k] === '') $errors[] = ucwords(str_replace('_', ' ', $k)) . ' is required.';
    if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($d['username'] && !preg_match('/^[A-Za-z0-9_.]{4,30}$/', $d['username'])) $errors[] = 'Username must be 4-30 letters, numbers, dots or underscores.';
    if (!$id && ($pe = password_errors($pw))) $errors[] = 'Password needs ' . implode(', ', $pe) . '.';
    if ($id && $pw !== '' && ($pe = password_errors($pw))) $errors[] = 'Password needs ' . implode(', ', $pe) . '.';
    if (!$errors) {
        $c = db()->prepare('SELECT COUNT(*) FROM users WHERE (username=? OR email=?) AND id<>?'); $c->execute([$d['username'], $d['email'], $id]);
        if ($c->fetchColumn()) $errors[] = 'Username or email already used.';
        $c = db()->prepare('SELECT COUNT(*) FROM student_profiles WHERE student_id=? AND user_id<>?'); $c->execute([$d['student_id'], $id]);
        if ($c->fetchColumn()) $errors[] = 'Student ID already used.';
    }
    $pic = $d['profile_picture'];
    if (!$errors && !empty($_FILES['profile_picture']['name'])) { try { $pic = save_upload($_FILES['profile_picture'], 'profiles', IMAGE_TYPES, 2097152)['name']; } catch (RuntimeException $x) { $errors[] = $x->getMessage(); } }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($id) {
                $pdo->prepare('UPDATE users SET username=?,email=?,status=? WHERE id=?')->execute([$d['username'], $d['email'], $d['status'], $id]);
                if ($pw !== '') $pdo->prepare('UPDATE users SET password_hash=?,must_change_password=1 WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
                $pdo->prepare('UPDATE student_profiles SET student_id=?,first_name=?,middle_name=?,last_name=?,course=?,year_level=?,section=?,profile_picture=? WHERE user_id=?')
                    ->execute([$d['student_id'], $d['first_name'], $d['middle_name'] ?: null, $d['last_name'], $d['course'], $d['year_level'], $d['section'], $pic, $id]);
                log_activity('student_updated', $d['username']);
            } else {
                $pdo->prepare('INSERT INTO users (username,email,password_hash,role,status) VALUES (?,?,?,"student",?)')->execute([$d['username'], $d['email'], password_hash($pw, PASSWORD_DEFAULT), $d['status']]);
                $nid = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO student_profiles (user_id,student_id,first_name,middle_name,last_name,course,year_level,section,profile_picture) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$nid, $d['student_id'], $d['first_name'], $d['middle_name'] ?: null, $d['last_name'], $d['course'], $d['year_level'], $d['section'], $pic]);
                log_activity('student_created', $d['username']);
            }
            $pdo->commit(); flash('success', 'Student saved.'); redirect('admin/students/index.php');
        } catch (PDOException $x) { $pdo->rollBack(); error_log($x->getMessage()); $errors[] = 'Database error while saving.'; }
    }
}
$pageTitle = $id ? 'Edit Student' : 'Add Student'; $activeNav = 'students';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-4" style="max-width:860px"><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
<?php foreach ([['student_id', 'Student ID', 4], ['first_name', 'First Name', 4], ['middle_name', 'Middle Name', 4], ['last_name', 'Last Name', 4], ['email', 'Email', 4], ['username', 'Username', 4], ['course', 'Course/Program', 4], ['year_level', 'Year Level', 4], ['section', 'Section', 4]] as [$n, $l, $c]): ?>
  <div class="col-md-<?= $c ?>"><label class="form-label"><?= $l ?></label><input name="<?= $n ?>" class="form-control" value="<?= e($d[$n]) ?>" <?= $n === 'middle_name' ? '' : 'required' ?>></div>
<?php endforeach; ?>
  <div class="col-md-4"><label class="form-label">Password <?= $id ? '(leave blank to keep)' : '' ?></label><div class="input-group"><input type="password" name="password" class="form-control" <?= $id ? '' : 'required' ?>><button type="button" class="btn btn-outline-secondary toggle-pw"><i class="fa fa-eye"></i></button></div></div>
  <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?= sel($d['status'], 'active') ?>>Active</option><option value="inactive" <?= sel($d['status'], 'inactive') ?>>Inactive</option></select></div>
  <div class="col-md-4"><label class="form-label">Profile Picture</label><input type="file" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="index.php">Cancel</a></div>
</form></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
