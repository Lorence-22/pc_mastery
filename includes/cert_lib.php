<?php
/** Issue a certificate for a passed assessment attempt (idempotent: one per attempt). */
function issue_certificate(int $attemptId): ?int {
    $pdo = db();
    $s = $pdo->prepare("SELECT a.*, m.title FROM assembly_attempts a JOIN assembly_modules m ON m.id=a.module_id WHERE a.id=? AND a.mode='assessment' AND a.status='completed' AND a.passed=1"); $s->execute([$attemptId]); $a = $s->fetch();
    if (!$a) return null;
    $x = $pdo->prepare('SELECT id FROM certificates WHERE attempt_id=?'); $x->execute([$attemptId]); if ($id = $x->fetchColumn()) return (int)$id;
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO certificates (certificate_no,user_id,attempt_id,title,score,issued_at) VALUES (?,?,?,?,?,NOW())')->execute(['TMP' . bin2hex(random_bytes(8)), $a['user_id'], $attemptId, $a['title'], $a['percentage']]);
        $id = (int)$pdo->lastInsertId(); $no = sprintf('PCM-%s-%06d', date('Y'), $id);
        $pdo->prepare('UPDATE certificates SET certificate_no=? WHERE id=?')->execute([$no, $id]); $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); error_log($e->getMessage()); return null; }
    log_activity('certificate_issued', $no, (int)$a['user_id']); notify((int)$a['user_id'], 'Congratulations! Your certificate ' . $no . ' is ready.', url('student/certificates/view.php?id=' . $id));
    return $id;
}
function get_certificate(int $id, ?int $userId = null): ?array {
    $sql = "SELECT c.*, CONCAT(sp.first_name,' ',IF(sp.middle_name IS NULL OR sp.middle_name='','',CONCAT(LEFT(sp.middle_name,1),'. ')),sp.last_name) student_name, sp.student_id FROM certificates c JOIN student_profiles sp ON sp.user_id=c.user_id WHERE c.id=?" . ($userId ? ' AND c.user_id=?' : '');
    $s = db()->prepare($sql); $s->execute($userId ? [$id, $userId] : [$id]); return $s->fetch() ?: null;
}
/** Full standalone HTML (table-based so Dompdf can render it as a landscape PDF too). */
function certificate_html(array $c, bool $forPdf = false): string {
    $inst = e(setting('institution_name', 'Institution')); $sig = e(setting('certificate_signatory', 'Administrator'));
    $date = e(date('F j, Y', strtotime($c['issued_at']))); $revoked = $c['status'] === 'revoked';
    $verify = e((!empty($_SERVER['HTTP_HOST']) ? (($_SERVER['HTTPS'] ?? '') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : '') . url('verify.php?no=' . $c['certificate_no']));
    $wm = $revoked ? '<div style="position:absolute;top:42%;left:0;right:0;text-align:center;font-size:90px;color:#dc2626;opacity:.25;font-weight:bold;">REVOKED</div>' : '';
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Certificate ' . e($c['certificate_no']) . '</title><style>
@page{size:A4 landscape;margin:0} *{box-sizing:border-box} html,body{margin:0;padding:0;background:#fff}
.sheet{position:relative;width:297mm;height:209mm;padding:10mm;font-family:"Times New Roman",serif;color:#0f172a}
.b1{border:6px solid #172554;height:100%;padding:5px}.b2{border:2px solid #06B6D4;height:100%;padding:14mm 16mm;text-align:center}
.inst{font-size:20px;letter-spacing:3px;text-transform:uppercase;color:#172554;font-weight:bold}.ttl{font-size:46px;color:#2563EB;font-weight:bold;margin:8px 0 2px}
.sub{font-size:15px;color:#475569;letter-spacing:2px;text-transform:uppercase}.nm{font-size:44px;font-style:italic;border-bottom:2px solid #172554;display:inline-block;padding:0 40px;margin:12px 0}
.ds{font-size:17px;line-height:1.6;margin:10px auto;max-width:200mm}.mt{width:100%;margin-top:18px}.mt td{width:33%;text-align:center;font-size:14px;vertical-align:bottom}
.line{border-top:1px solid #0f172a;margin:0 14mm;padding-top:4px}.small{font-size:11px;color:#64748b;margin-top:10px}
@media screen{body{background:#e2e8f0}.sheet{margin:20px auto;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,.2)}}
@media print{.no-print{display:none!important}}
</style></head><body><div class="sheet">' . $wm . '<div class="b1"><div class="b2">
<div class="inst">' . $inst . '</div><div class="sub">PC Mastery Learning System</div><div class="ttl">Certificate of Achievement</div><div class="sub">This certificate is proudly awarded to</div>
<div class="nm">' . e($c['student_name']) . '</div>
<div class="ds">for successfully completing the <b>' . e($c['title']) . '</b> and demonstrating the knowledge and skills required to correctly identify, install and connect desktop computer components, with a final score of <b>' . e($c['score']) . '%</b>.</div>
<table class="mt"><tr><td><b>' . $date . '</b><div class="line">Date of Completion</div></td><td><b>' . e($c['certificate_no']) . '</b><div class="line">Certificate No.</div></td><td><div style="height:30px"></div><div class="line"><b>' . $sig . '</b><br>Authorized Administrator</div></td></tr></table>
<div class="small">Verify authenticity at ' . $verify . '</div></div></div></div></body></html>';
}
