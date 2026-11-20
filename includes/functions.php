<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function csrf_verify(?string $t = null): bool {
    $t = $t ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t);
}
function require_csrf(): void {
    if (!csrf_verify()) { http_response_code(419); exit('Invalid or expired security token. Go back and reload the page.'); }
}

/* ---------- Flash (SweetAlert2) ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function pull_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

/* ---------- Settings, logs ---------- */
function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM system_settings') as $r) $cache[$r['setting_key']] = $r['setting_value'];
    }
    return $cache[$key] ?? $default;
}
function log_activity(string $action, string $details = '', ?int $uid = null): void {
    $uid = $uid ?? ($_SESSION['user_id'] ?? null);
    db()->prepare('INSERT INTO activity_logs (user_id,action,details,ip_address) VALUES (?,?,?,?)')
        ->execute([$uid, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}
function notify(int $userId, string $message, ?string $link = null): void {
    db()->prepare('INSERT INTO notifications (user_id,message,link) VALUES (?,?,?)')->execute([$userId, $message, $link]);
}

/* ---------- Auth ---------- */
function ensure_default_admin(): void {
    $row = db()->query("SELECT id,password_hash FROM users WHERE username='admin' AND role='admin' LIMIT 1")->fetch();
    if ($row && $row['password_hash'] === 'INIT') {
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')
            ->execute([password_hash('Admin@12345', PASSWORD_DEFAULT), $row['id']]);
    }
}
function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['user_id'])) {
            $s = db()->prepare('SELECT u.id,u.username,u.email,u.role,u.status,u.must_change_password,
                COALESCE(CONCAT(sp.first_name," ",sp.last_name),ap.full_name) AS full_name,
                COALESCE(sp.profile_picture,ap.profile_picture) AS picture
                FROM users u LEFT JOIN student_profiles sp ON sp.user_id=u.id LEFT JOIN admin_profiles ap ON ap.user_id=u.id
                WHERE u.id=?');
            $s->execute([$_SESSION['user_id']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u || $u['status'] !== 'active') { session_destroy(); redirect('auth/login.php'); }
    if ($role && $u['role'] !== $role) { http_response_code(403); exit('Access denied.'); }
    $self = basename($_SERVER['SCRIPT_NAME']);
    if ($u['must_change_password'] && $self !== 'change_password.php' && $self !== 'logout.php') redirect('auth/change_password.php');
    return $u;
}
function password_errors(string $p): array {
    $e = [];
    if (strlen($p) < 8) $e[] = 'at least 8 characters';
    if (!preg_match('/[A-Z]/', $p)) $e[] = 'an uppercase letter';
    if (!preg_match('/[a-z]/', $p)) $e[] = 'a lowercase letter';
    if (!preg_match('/\d/', $p)) $e[] = 'a number';
    return $e;
}

/* ---------- Secure uploads ---------- */
function save_upload(array $file, string $dir, array $allowed, int $maxBytes = MAX_UPLOAD_BYTES): array {
    // $allowed: ext => [mime,...]
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (code ' . $file['error'] . ').');
    if ($file['size'] > $maxBytes) throw new RuntimeException('File exceeds the maximum size of ' . round($maxBytes / 1048576) . ' MB.');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$ext]) || !in_array($mime, $allowed[$ext], true)) throw new RuntimeException('Invalid file type.');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = __DIR__ . '/../uploads/' . trim($dir, '/') . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Could not save the file.');
    return ['name' => $name, 'original' => basename($file['name']), 'ext' => $ext];
}
const IMAGE_TYPES = ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp']];
const PDF_TYPES = ['pdf' => ['application/pdf']];

/* ---------- Pagination, modal, misc ---------- */
function paginate(string $countSql, array $params, int $per = 10): array {
    $s = db()->prepare($countSql); $s->execute($params); $total = (int)$s->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));
    $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
    return ['total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $per, 'offset' => ($page - 1) * $per];
}
function pager(array $pg): string {
    if ($pg['pages'] <= 1) return '';
    $q = $_GET; $h = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    for ($i = 1; $i <= $pg['pages']; $i++) {
        if ($pg['pages'] > 9 && abs($i - $pg['page']) > 3 && $i !== 1 && $i !== $pg['pages']) continue;
        $q['page'] = $i;
        $h .= '<li class="page-item ' . ($i === $pg['page'] ? 'active' : '') . '"><a class="page-link" href="?' . e(http_build_query($q)) . '">' . $i . '</a></li>';
    }
    return $h . '</ul></nav>';
}
function modal_msg(string $title, string $html): void { $_SESSION['modal'] = ['title' => $title, 'html' => $html]; }
function pull_modal(): ?array { $m = $_SESSION['modal'] ?? null; unset($_SESSION['modal']); return $m; }
function slugify(string $s): string { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-') ?: 'item'; }
function temp_password(): string { return 'Pc' . bin2hex(random_bytes(3)) . random_int(10, 99) . 'x'; }
function sel($a, $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function fmt_dt(?string $d): string { return $d ? date('M j, Y g:i A', strtotime($d)) : '-'; }
function get_part_ids_done(int $uid, string $type): array {
    $s = db()->prepare('SELECT item_id FROM material_progress WHERE user_id=? AND item_type=?'); $s->execute([$uid, $type]);
    return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
}
function mark_progress(int $uid, string $type, int $id): void {
    db()->prepare('INSERT IGNORE INTO material_progress (user_id,item_type,item_id) VALUES (?,?,?)')->execute([$uid, $type, $id]);
}
