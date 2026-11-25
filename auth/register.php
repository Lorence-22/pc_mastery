<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user()) redirect('student/dashboard.php');
$errors = []; $old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $f = ['student_id','first_name','middle_name','last_name','email','username','course','year_level','section'];
    foreach ($f as $k) $old[$k] = trim($_POST[$k] ?? '');
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['confirm_password'] ?? '';
    foreach (['student_id','first_name','last_name','email','username','course','year_level','section'] as $k)
        if ($old[$k] === '') $errors[] = ucwords(str_replace('_', ' ', $k)) . ' is required.';
    if ($old['email'] && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if ($old['username'] && !preg_match('/^[A-Za-z0-9_.]{4,30}$/', $old['username'])) $errors[] = 'Username must be 4-30 letters, numbers, dots or underscores.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if ($pe = password_errors($pw)) $errors[] = 'Password needs ' . implode(', ', $pe) . '.';
    if (!$errors) {
        $c = db()->prepare('SELECT COUNT(*) FROM users WHERE username=? OR email=?'); $c->execute([$old['username'], $old['email']]);
        if ($c->fetchColumn()) $errors[] = 'Username or email already registered.';
        $c = db()->prepare('SELECT COUNT(*) FROM student_profiles WHERE student_id=?'); $c->execute([$old['student_id']]);
        if ($c->fetchColumn()) $errors[] = 'Student ID already registered.';
    }
    $pic = null;
    if (!$errors && !empty($_FILES['profile_picture']['name'])) {
        try { $pic = save_upload($_FILES['profile_picture'], 'profiles', IMAGE_TYPES, 2 * 1048576)['name']; }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (username,email,password_hash,role) VALUES (?,?,?,"student")')
                ->execute([$old['username'], $old['email'], password_hash($pw, PASSWORD_DEFAULT)]);
            $uid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO student_profiles (user_id,student_id,first_name,middle_name,last_name,course,year_level,section,profile_picture) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$uid, $old['student_id'], $old['first_name'], $old['middle_name'] ?: null, $old['last_name'], $old['course'], $old['year_level'], $old['section'], $pic]);
            $pdo->commit();
            log_activity('student_registered', "Student {$old['student_id']} registered", $uid);
            notify($uid, 'Welcome to PC Mastery! Start with the Computer Parts lessons.', url('student/lessons/index.php'));
            flash('success', 'Registration successful. Please log in.');
            redirect('auth/login.php');
        } catch (Throwable $ex) { $pdo->rollBack(); error_log($ex->getMessage()); $errors[] = 'Registration failed. Please try again.'; }
    }
}
$pageTitle = 'Register'; $wide = true;
include __DIR__ . '/../includes/auth_header.php';
?>
<h2 class="h4 fw-bold mb-3">Create a student account</h2>
<?php if ($errors): ?><div class="alert alert-danger py-2"><ul class="mb-0 ps-3"><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3"
      style="background-color: #ffe5e5; padding: 25px; border-radius: 12px;">
<?= csrf_field() ?>

<?php
$fields = [
    ['student_id','Student ID',6],
    ['email','Email Address',6,'email'],
    ['first_name','First Name',4],
    ['middle_name','Middle Name',4],
    ['last_name','Last Name',4],
    ['username','Username',6],
    ['course','Course/Program',6],
    ['year_level','Year Level',6],
    ['section','Section',6]
];

foreach ($fields as $f):
    [$n,$l,$c] = $f;
    $t = $f[3] ?? 'text';
?>
    <div class="col-md-<?= $c ?>">
        <label class="form-label" style="color: #cc0000; font-weight: bold;">
            <?= $l ?>
        </label>
        <input type="<?= $t ?>" name="<?= $n ?>"
               class="form-control"
               style="border: 1px solid #cc0000;"
               value="<?= e($old[$n] ?? '') ?>"
               <?= $n==='middle_name'?'':'required' ?>>
    </div>
<?php endforeach; ?>

<?php foreach (['password'=>'Password','confirm_password'=>'Confirm Password'] as $n=>$l): ?>
    <div class="col-md-6">
        <label class="form-label" style="color: #cc0000; font-weight: bold;">
            <?= $l ?>
        </label>
        <div class="input-group">
            <input type="password" name="<?= $n ?>"
                   class="form-control"
                   style="border: 1px solid #cc0000;"
                   required>
            <button type="button" class="btn toggle-pw"
                    style="background-color: #cc0000; color: white;"
                    tabindex="-1">
                <i class="fa fa-eye"></i>
            </button>
        </div>
    </div>
<?php endforeach; ?>

<div class="col-12">
    <label class="form-label" style="color: #cc0000; font-weight: bold;">
        Profile Picture (optional, JPG/PNG/WEBP, max 2 MB)
    </label>
    <input type="file" name="profile_picture"
           class="form-control"
           style="border: 1px solid #cc0000;"
           accept=".jpg,.jpeg,.png,.webp">
</div>

<div class="col-12">
    <button class="btn btn-lg w-100"
            style="background-color: #cc0000; color: white; border: none;">
        Register
    </button>
</div>

</form>

<p class="text-center mt-3 mb-0">Already registered? <a href="<?= url('auth/login.php') ?>">Sign in</a></p>
<?php include __DIR__ . '/../includes/auth_footer.php'; ?>
