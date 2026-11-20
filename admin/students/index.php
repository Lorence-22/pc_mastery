<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0); $act = $_POST['action'] ?? '';
    $t = db()->prepare("SELECT username FROM users WHERE id=? AND role='student'"); $t->execute([$id]); $name = $t->fetchColumn();
    if ($name) {
        if ($act === 'toggle') {
            db()->prepare("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=?")->execute([$id]);
            log_activity('student_status_toggled', "Toggled $name"); flash('success', 'Account status updated.');
        } elseif ($act === 'delete') {
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            log_activity('student_deleted', "Deleted $name"); flash('success', 'Student deleted.');
        } elseif ($act === 'reset') {
            $pw = temp_password();
            db()->prepare('UPDATE users SET password_hash=?, must_change_password=1 WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            log_activity('student_password_reset', "Reset password for $name");
            modal_msg('Temporary password', 'Give this to <b>' . e($name) . '</b>. They must change it at next login.<br><code class="fs-4">' . e($pw) . '</code>');
        }
    }
    redirect('admin/students/index.php?' . http_build_query(array_diff_key($_GET, ['x' => 1])));
}
$w = ["u.role='student'"]; $p = [];
if (($qs = trim($_GET['q'] ?? '')) !== '') { $w[] = '(sp.first_name LIKE ? OR sp.last_name LIKE ? OR sp.student_id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)'; array_push($p, ...array_fill(0, 5, "%$qs%")); }
foreach (['course' => 'sp.course', 'year_level' => 'sp.year_level', 'section' => 'sp.section', 'status' => 'u.status'] as $k => $col)
    if (($v = trim($_GET[$k] ?? '')) !== '') { $w[] = "$col=?"; $p[] = $v; }
$where = implode(' AND ', $w);
$pg = paginate("SELECT COUNT(*) FROM users u JOIN student_profiles sp ON sp.user_id=u.id WHERE $where", $p);
$s = db()->prepare("SELECT u.id,u.username,u.email,u.status,u.last_login,sp.* FROM users u JOIN student_profiles sp ON sp.user_id=u.id WHERE $where ORDER BY sp.last_name,sp.first_name LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$s->execute($p); $rows = $s->fetchAll();
$courses = db()->query('SELECT DISTINCT course FROM student_profiles ORDER BY course')->fetchAll(PDO::FETCH_COLUMN);
$years = db()->query('SELECT DISTINCT year_level FROM student_profiles ORDER BY year_level')->fetchAll(PDO::FETCH_COLUMN);
$sections = db()->query('SELECT DISTINCT section FROM student_profiles ORDER BY section')->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Student Management'; $activeNav = 'students';
include __DIR__ . '/../../includes/header.php';
$opt = fn($arr, $k) => implode('', array_map(fn($v) => '<option ' . sel($_GET[$k] ?? '', $v) . '>' . e($v) . '</option>', $arr));
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get">
  <div class="col-md-3"><input name="q" class="form-control" placeholder="Search name, ID, username, email" value="<?= e($qs) ?>"></div>
  <div class="col-md-2"><select name="course" class="form-select"><option value="">All courses</option><?= $opt($courses, 'course') ?></select></div>
  <div class="col-md-2"><select name="year_level" class="form-select"><option value="">All years</option><?= $opt($years, 'year_level') ?></select></div>
  <div class="col-md-2"><select name="section" class="form-select"><option value="">All sections</option><?= $opt($sections, 'section') ?></select></div>
  <div class="col-md-1"><select name="status" class="form-select"><option value="">Any</option><option value="active" <?= sel($_GET['status'] ?? '', 'active') ?>>Active</option><option value="inactive" <?= sel($_GET['status'] ?? '', 'inactive') ?>>Inactive</option></select></div>
  <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary flex-fill"><i class="fa fa-search"></i></button><a class="btn btn-outline-secondary" href="?">Reset</a><a class="btn btn-success" href="form.php"><i class="fa fa-plus"></i></a></div>
</form></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle table-hover">
<thead><tr><th>Student ID</th><th>Name</th><th>Course / Year / Section</th><th>Username</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
  <td><?= e($r['student_id']) ?></td><td><?= e($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name']) ?><div class="small text-muted"><?= e($r['email']) ?></div></td>
  <td><?= e($r['course']) ?> / <?= e($r['year_level']) ?> / <?= e($r['section']) ?></td><td><?= e($r['username']) ?></td>
  <td><span class="badge text-bg-<?= $r['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($r['status']) ?></span></td>
  <td class="text-end text-nowrap">
    <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= $r['user_id'] ?>" title="View"><i class="fa fa-eye"></i></a>
    <a class="btn btn-sm btn-outline-secondary" href="form.php?id=<?= $r['user_id'] ?>" title="Edit"><i class="fa fa-pen"></i></a>
    <?php foreach ([['toggle', $r['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check', 'warning', 'Change account status?'], ['reset', 'fa-key', 'info', 'Reset this password?'], ['delete', 'fa-trash', 'danger', 'This permanently deletes the student and all their records.']] as [$a, $i, $c, $m]): ?>
      <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['user_id'] ?>"><input type="hidden" name="action" value="<?= $a ?>">
      <button class="btn btn-sm btn-outline-<?= $c ?>" data-confirm="<?= e($m) ?>" title="<?= $a ?>"><i class="fa <?= $i ?>"></i></button></form>
    <?php endforeach; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6"><div class="empty-state"><i class="fa fa-users"></i><p>No students found.</p></div></td></tr><?php endif; ?>
</tbody></table></div><div class="d-flex justify-content-between align-items-center"><small class="text-muted"><?= $pg['total'] ?> student(s)</small><?= pager($pg) ?></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
