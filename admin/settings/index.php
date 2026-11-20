<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $act = $_POST['action'] ?? '';
    if ($act === 'settings') {
        $vals = ['institution_name' => trim($_POST['institution_name'] ?? '') ?: 'Your School Name', 'certificate_signatory' => trim($_POST['certificate_signatory'] ?? '') ?: 'Administrator',
            'assembly_passing_score' => (string)min(100, max(1, (float)($_POST['assembly_passing_score'] ?? 80))), 'leaderboard_enabled' => empty($_POST['leaderboard_enabled']) ? '0' : '1', 'max_upload_mb' => (string)min(50, max(1, (int)($_POST['max_upload_mb'] ?? 10)))];
        $up = db()->prepare('INSERT INTO system_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'); foreach ($vals as $k => $v) $up->execute([$k, $v]);
        if (!empty($_POST['apply_passing'])) db()->prepare('UPDATE assembly_modules SET passing_score=?')->execute([$vals['assembly_passing_score']]);
        log_activity('settings_updated'); flash('success', 'Settings saved.');
    } elseif ($act === 'announce' && trim($_POST['title'] ?? '') !== '' && trim($_POST['body'] ?? '') !== '') {
        db()->prepare('INSERT INTO announcements (title,body,created_by) VALUES (?,?,?)')->execute([trim($_POST['title']), trim($_POST['body']), $user['id']]);
        $n = db()->prepare('INSERT INTO notifications (user_id,message,link) SELECT id, ?, ? FROM users WHERE role="student" AND status="active"'); $n->execute(['Announcement: ' . mb_strimwidth(trim($_POST['title']), 0, 200, '...'), url('student/dashboard.php')]);
        log_activity('announcement_posted', trim($_POST['title'])); flash('success', 'Announcement posted and students notified.');
    } elseif ($act === 'del_announce') { db()->prepare('DELETE FROM announcements WHERE id=?')->execute([(int)$_POST['id']]); flash('success', 'Announcement deleted.'); }
    redirect('admin/settings/index.php');
}
$ann = db()->query('SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20')->fetchAll();
$pageTitle = 'System Settings'; $activeNav = 'settings';
include __DIR__ . '/../../includes/header.php';
?>
<div class="row g-3"><div class="col-lg-6"><div class="card p-4"><h6>General</h6><form method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="settings">
<div class="col-12"><label class="form-label">Institution name (shown on certificates)</label><input name="institution_name" class="form-control" value="<?= e(setting('institution_name')) ?>"></div>
<div class="col-12"><label class="form-label">Certificate signatory (authorized administrator)</label><input name="certificate_signatory" class="form-control" value="<?= e(setting('certificate_signatory')) ?>"></div>
<div class="col-md-6"><label class="form-label">Default assembly passing score (%)</label><input type="number" name="assembly_passing_score" min="1" max="100" class="form-control" value="<?= e(setting('assembly_passing_score', 80)) ?>"></div><div class="col-md-6"><label class="form-label">Max upload size (MB)</label><input type="number" name="max_upload_mb" min="1" max="50" class="form-control" value="<?= e(setting('max_upload_mb', 10)) ?>"></div>
<div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" name="apply_passing" id="ap"><label class="form-check-label" for="ap">Apply this passing score to all existing assembly modules</label></div><div class="form-check"><input type="checkbox" class="form-check-input" name="leaderboard_enabled" id="lb" <?= setting('leaderboard_enabled') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="lb">Enable student leaderboard</label></div></div>
<div class="col-12"><button class="btn btn-primary">Save settings</button> <a class="btn btn-outline-secondary" href="<?= url('auth/change_password.php') ?>">Change my password</a></div></form></div></div>
<div class="col-lg-6"><div class="card p-4"><h6>Announcements</h6><form method="post" class="mb-3"><?= csrf_field() ?><input type="hidden" name="action" value="announce"><input name="title" class="form-control mb-2" placeholder="Title" required><textarea name="body" rows="2" class="form-control mb-2" placeholder="Message to all students" required></textarea><button class="btn btn-success btn-sm"><i class="fa fa-bullhorn"></i> Post</button></form>
<?php foreach ($ann as $a): ?><div class="border rounded p-2 mb-2 d-flex justify-content-between"><div><b><?= e($a['title']) ?></b><div class="small"><?= e($a['body']) ?></div><small class="text-muted"><?= fmt_dt($a['created_at']) ?></small></div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_announce"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete announcement?"><i class="fa fa-trash"></i></button></form></div><?php endforeach; if (!$ann) echo '<small class="text-muted">No announcements.</small>'; ?></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
