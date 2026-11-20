<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $conf = $_POST['confirm'] ?? '';
    $s = db()->prepare('SELECT password_hash FROM users WHERE id=?'); $s->execute([$user['id']]);
    if (!password_verify($cur, $s->fetchColumn())) $errors[] = 'Current password is incorrect.';
    if ($new !== $conf) $errors[] = 'New passwords do not match.';
    if ($new === $cur) $errors[] = 'New password must differ from the current password.';
    if ($pe = password_errors($new)) $errors[] = 'Password needs ' . implode(', ', $pe) . '.';
    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        log_activity('password_changed');
        flash('success', 'Password updated.');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
    }
}
$pageTitle = 'Change Password';
include __DIR__ . '/../includes/auth_header.php';
?>
<h2 class="h4 fw-bold">Change your password</h2>
<?php if ($user['must_change_password']): ?><div class="alert alert-warning py-2">You must change the default password before continuing.</div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
<?php foreach (['current'=>'Current password','new'=>'New password','confirm'=>'Confirm new password'] as $n=>$l): ?>
  <div class="mb-3"><label class="form-label"><?= $l ?></label>
  <div class="input-group"><input type="password" name="<?= $n ?>" class="form-control" required>
  <button type="button" class="btn btn-outline-secondary toggle-pw" tabindex="-1"><i class="fa fa-eye"></i></button></div></div>
<?php endforeach; ?>
<button class="btn btn-primary w-100">Update Password</button>
<?php if (!$user['must_change_password']): ?><a class="btn btn-link w-100 mt-1" href="<?= url($user['role']==='admin'?'admin/dashboard.php':'student/dashboard.php') ?>">Cancel</a><?php endif; ?>
</form>
<?php include __DIR__ . '/../includes/auth_footer.php'; ?>
