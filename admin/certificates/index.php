<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $id = (int)$_POST['id']; $act = $_POST['action'] ?? '';
    $c = db()->prepare('SELECT certificate_no,user_id FROM certificates WHERE id=?'); $c->execute([$id]); $c = $c->fetch();
    if ($c && $act === 'revoke') { db()->prepare("UPDATE certificates SET status='revoked', revoked_reason=? WHERE id=?")->execute([trim($_POST['reason'] ?? '') ?: 'Revoked by administrator', $id]); log_activity('certificate_revoked', $c['certificate_no']); notify((int)$c['user_id'], 'Certificate ' . $c['certificate_no'] . ' has been revoked.'); flash('success', 'Certificate revoked.'); }
    if ($c && $act === 'restore') { db()->prepare("UPDATE certificates SET status='valid', revoked_reason=NULL WHERE id=?")->execute([$id]); log_activity('certificate_restored', $c['certificate_no']); flash('success', 'Certificate restored.'); }
    redirect('admin/certificates/index.php');
}
$qs = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? ''; $w = ['1=1']; $p = [];
if ($qs !== '') { $w[] = '(c.certificate_no LIKE ? OR sp.first_name LIKE ? OR sp.last_name LIKE ? OR sp.student_id LIKE ?)'; array_push($p, ...array_fill(0, 4, "%$qs%")); }
if (in_array($st, ['valid', 'revoked'], true)) { $w[] = 'c.status=?'; $p[] = $st; }
$where = implode(' AND ', $w); $pg = paginate("SELECT COUNT(*) FROM certificates c JOIN student_profiles sp ON sp.user_id=c.user_id WHERE $where", $p);
$s = db()->prepare("SELECT c.*, CONCAT(sp.first_name,' ',sp.last_name) name, sp.student_id sid FROM certificates c JOIN student_profiles sp ON sp.user_id=c.user_id WHERE $where ORDER BY c.issued_at DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}"); $s->execute($p); $rows = $s->fetchAll();
$pageTitle = 'Certificate Management'; $activeNav = 'certificates';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-6"><input name="q" class="form-control" placeholder="Search certificate no., student name or ID" value="<?= e($qs) ?>"></div><div class="col-md-3"><select name="status" class="form-select"><option value="">All</option><option value="valid" <?= sel($st, 'valid') ?>>Valid</option><option value="revoked" <?= sel($st, 'revoked') ?>>Revoked</option></select></div><div class="col-md-3 d-flex gap-1"><button class="btn btn-primary flex-fill">Search</button><a class="btn btn-outline-secondary" target="_blank" href="<?= url('verify.php') ?>">Verify page</a></div></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Number</th><th>Student</th><th>Title</th><th>Score</th><th>Issued</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><code><?= e($r['certificate_no']) ?></code></td><td><?= e($r['name']) ?><div class="small text-muted"><?= e($r['sid']) ?></div></td><td><?= e($r['title']) ?></td><td><?= e($r['score']) ?>%</td><td><?= fmt_dt($r['issued_at']) ?></td>
<td><span class="badge text-bg-<?= $r['status'] === 'valid' ? 'success' : 'danger' ?>"><?= e($r['status']) ?></span><?= $r['revoked_reason'] ? '<div class="small text-muted">' . e($r['revoked_reason']) . '</div>' : '' ?></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= url('student/certificates/view.php?id=' . $r['id']) ?>"><i class="fa fa-eye"></i></a>
<form method="post" class="d-inline-flex gap-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
<?php if ($r['status'] === 'valid'): ?><input name="reason" class="form-control form-control-sm" style="width:130px" placeholder="Reason"><button name="action" value="revoke" class="btn btn-sm btn-outline-danger" data-confirm="Revoke this certificate?">Revoke</button>
<?php else: ?><button name="action" value="restore" class="btn btn-sm btn-outline-success">Restore</button><?php endif; ?></form></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="7"><div class="empty-state"><i class="fa fa-certificate"></i><p>No certificates found.</p></div></td></tr>'; ?></tbody></table></div><?= pager($pg) ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
