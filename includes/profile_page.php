<?php /** expects $user. Handles both roles. */
$isAdmin = $user['role'] === 'admin'; $errors = [];
$s = db()->prepare($isAdmin ? 'SELECT u.email,ap.full_name,ap.position,ap.profile_picture FROM users u JOIN admin_profiles ap ON ap.user_id=u.id WHERE u.id=?'
    : 'SELECT u.email,sp.* FROM users u JOIN student_profiles sp ON sp.user_id=u.id WHERE u.id=?');
$s->execute([$user['id']]); $d = $s->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    $c = db()->prepare('SELECT COUNT(*) FROM users WHERE email=? AND id<>?'); $c->execute([$email, $user['id']]); if ($c->fetchColumn()) $errors[] = 'Email already used.';
    $pic = $d['profile_picture'];
    if (!$errors && !empty($_FILES['profile_picture']['name'])) { try { $pic = save_upload($_FILES['profile_picture'], 'profiles', IMAGE_TYPES, 2097152)['name']; } catch (RuntimeException $x) { $errors[] = $x->getMessage(); } }
    if ($isAdmin) { $full = trim($_POST['full_name'] ?? ''); $pos = trim($_POST['position'] ?? ''); if ($full === '') $errors[] = 'Full name is required.'; }
    else { $f = []; foreach (['first_name', 'middle_name', 'last_name', 'course', 'year_level', 'section'] as $k) $f[$k] = trim($_POST[$k] ?? '');
        foreach (['first_name', 'last_name', 'course', 'year_level', 'section'] as $k) if ($f[$k] === '') $errors[] = ucwords(str_replace('_', ' ', $k)) . ' is required.'; }
    if (!$errors) {
        db()->prepare('UPDATE users SET email=? WHERE id=?')->execute([$email, $user['id']]);
        if ($isAdmin) db()->prepare('UPDATE admin_profiles SET full_name=?,position=?,profile_picture=? WHERE user_id=?')->execute([$full, $pos, $pic, $user['id']]);
        else db()->prepare('UPDATE student_profiles SET first_name=?,middle_name=?,last_name=?,course=?,year_level=?,section=?,profile_picture=? WHERE user_id=?')
            ->execute([$f['first_name'], $f['middle_name'] ?: null, $f['last_name'], $f['course'], $f['year_level'], $f['section'], $pic, $user['id']]);
        log_activity('profile_updated'); flash('success', 'Profile updated.'); redirect($isAdmin ? 'admin/profile.php' : 'student/profile.php');
    }
    $d = array_merge($d, $_POST);
}
$pageTitle = 'My Profile'; $activeNav = 'profile';
include __DIR__ . '/header.php';
$fields = $isAdmin ? [['full_name', 'Full Name', 6], ['position', 'Position', 6], ['email', 'Email', 6]]
    : [['first_name', 'First Name', 4], ['middle_name', 'Middle Name', 4], ['last_name', 'Last Name', 4], ['email', 'Email', 6], ['course', 'Course/Program', 6], ['year_level', 'Year Level', 3], ['section', 'Section', 3]];
?>
<div class="row g-3"><div class="col-lg-8"><div class="card p-4"><?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
<?php if (!$isAdmin): ?><div class="col-md-6"><label class="form-label">Student ID</label><input class="form-control" value="<?= e($d['student_id']) ?>" disabled></div><div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="<?= e($user['username']) ?>" disabled></div><?php endif; ?>
<?php foreach ($fields as [$n, $l, $c]): ?><div class="col-md-<?= $c ?>"><label class="form-label"><?= $l ?></label><input name="<?= $n ?>" class="form-control" value="<?= e($d[$n] ?? '') ?>" <?= in_array($n, ['middle_name', 'position']) ? '' : 'required' ?>></div><?php endforeach; ?>
<div class="col-12"><label class="form-label">Profile Picture</label><input type="file" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save Changes</button><a class="btn btn-outline-secondary" href="<?= url('auth/change_password.php') ?>"><i class="fa fa-key"></i> Change Password</a></div></form></div></div>
<div class="col-lg-4"><div class="card p-4 text-center"><?php if ($d['profile_picture']): ?><img src="<?= url('uploads/profiles/' . e($d['profile_picture'])) ?>" class="rounded-circle mx-auto mb-2" style="width:110px;height:110px;object-fit:cover"><?php else: ?><span class="avatar avatar-ph mx-auto mb-2" style="width:110px;height:110px;font-size:2.5rem"><?= e(strtoupper($user['full_name'][0])) ?></span><?php endif; ?>
<h5><?= e($user['full_name']) ?></h5><span class="badge text-bg-primary text-capitalize"><?= e($user['role']) ?></span>
<?php if (!$isAdmin): ?><hr><a class="btn btn-sm btn-outline-primary w-100 mb-2" href="<?= url('student/progress.php') ?>">Learning history</a><a class="btn btn-sm btn-outline-success w-100" href="<?= url('student/certificates/index.php') ?>">My certificates</a><?php endif; ?></div></div></div>
<?php include __DIR__ . '/footer.php'; ?>
