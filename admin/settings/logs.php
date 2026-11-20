<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin'); $qs = trim($_GET['q'] ?? ''); $from = trim($_GET['from'] ?? ''); $to = trim($_GET['to'] ?? '');
$w = ['1=1']; $p = []; if ($qs !== '') { $w[] = '(l.action LIKE ? OR l.details LIKE ? OR u.username LIKE ?)'; array_push($p, "%$qs%", "%$qs%", "%$qs%"); } if ($from !== '') { $w[] = 'DATE(l.created_at)>=?'; $p[] = $from; } if ($to !== '') { $w[] = 'DATE(l.created_at)<=?'; $p[] = $to; }
$where = implode(' AND ', $w); $pg = paginate("SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id WHERE $where", $p, 25);
$s = db()->prepare("SELECT l.*, u.username, u.role FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id WHERE $where ORDER BY l.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}"); $s->execute($p); $rows = $s->fetchAll();
$pageTitle = 'Activity Logs'; $activeNav = 'logs';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form class="row g-2" method="get"><div class="col-md-5"><input name="q" class="form-control" placeholder="Search action, details or username" value="<?= e($qs) ?>"></div><div class="col-md-3"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div><div class="col-md-3"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div><div class="col-md-1"><button class="btn btn-primary w-100">Go</button></div></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td class="text-nowrap"><?= fmt_dt($r['created_at']) ?></td><td><?= e($r['username'] ?? 'guest') ?> <small class="text-muted"><?= e($r['role']) ?></small></td><td><span class="badge text-bg-light"><?= e($r['action']) ?></span></td><td><?= e($r['details']) ?></td><td><?= e($r['ip_address']) ?></td></tr><?php endforeach; if (!$rows) echo '<tr><td colspan="5" class="text-muted">No logs.</td></tr>'; ?></tbody></table></div><?= pager($pg) ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
