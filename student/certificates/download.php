<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/cert_lib.php';
$user = require_login(); $c = get_certificate((int)($_GET['id'] ?? 0), $user['role'] === 'student' ? (int)$user['id'] : null);
if (!$c) { http_response_code(404); exit('Certificate not found.'); }
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    $opts = new Dompdf\Options(); $opts->set('isRemoteEnabled', false); $opts->set('defaultFont', 'DejaVu Serif');
    $pdf = new Dompdf\Dompdf($opts); $pdf->loadHtml(certificate_html($c, true)); $pdf->setPaper('A4', 'landscape'); $pdf->render();
    log_activity('certificate_downloaded', $c['certificate_no']);
    $pdf->stream('Certificate-' . $c['certificate_no'] . '.pdf', ['Attachment' => true]); exit;
}
// Fallback when Dompdf is not installed: open the printable certificate (browser "Save as PDF").
redirect('student/certificates/view.php?id=' . $c['id'] . '&print=1');
