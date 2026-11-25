<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_default_admin();
if (current_user()) redirect(current_user()['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = trim($_POST['username'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['attempts'] = $_SESSION['attempts'] ?? 0;
    if ($_SESSION['attempts'] >= 5 && time() < ($_SESSION['lock_until'] ?? 0)) {
        $error = 'Too many failed attempts. Try again in a few minutes.';
    } else {
        $s = db()->prepare('SELECT * FROM users WHERE username=? OR email=? LIMIT 1');
        $s->execute([$username, $username]);
        $u = $s->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            if ($u['status'] !== 'active') {
                $error = 'Your account is deactivated. Contact the administrator.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$u['id'];
                $_SESSION['attempts'] = 0;
                db()->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$u['id']]);
                log_activity('login', "User {$u['username']} logged in", (int)$u['id']);
                redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
            }
        } else {
            $_SESSION['attempts']++;
            if ($_SESSION['attempts'] >= 5) $_SESSION['lock_until'] = time() + 300;
            $error = 'Invalid username or password.';
            log_activity('login_failed', "Failed login for '$username' from $ip", null);
        }
    }
}
$pageTitle = 'Login';
include __DIR__ . '/../includes/auth_header.php';
?>
<h2 class="h4 fw-bold mb-1">Welcome back</h2>
<p class="text-muted mb-4">Sign in to continue learning.</p>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
<form method="post" novalidate>
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label" style="color: red;">Username or Email</label>
    <input name="username" class="form-control form-control-lg" required autofocus>
  </div>

  <div class="mb-3">
    <label class="form-label" style="color: red;">Password</label>
    <div class="input-group">
      <input type="password" name="password" class="form-control form-control-lg" required>
      <button type="button" class="btn btn-outline-secondary toggle-pw" tabindex="-1">
        <i class="fa fa-eye"></i>
      </button>
    </div>
  </div>

  <button class="btn btn-primary btn-lg w-100" style="background-color: red; border-color: red; color: white;">
    Sign In
  </button>
</form>
<p class="text-center mt-3 mb-0">No account? <a href="<?= url('auth/register.php') ?>">Register as a student</a></p>
<?php include __DIR__ . '/../includes/auth_footer.php'; ?>
