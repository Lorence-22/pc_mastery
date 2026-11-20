<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/cert_lib.php';
$user = require_login(); $c = get_certificate((int)($_GET['id'] ?? 0), $user['role'] === 'student' ? (int)$user['id'] : null);
if (!$c) { http_response_code(404); exit('Certificate not found.'); }
$html = certificate_html($c);
$bar = '<div class="no-print" style="position:sticky;top:0;background:#172554;padding:10px;text-align:center;z-index:10;font-family:sans-serif"><a href="' . e(url($user['role'] === 'admin' ? 'admin/certificates/index.php' : 'student/certificates/index.php')) . '" style="color:#fff;margin-right:14px">&larr; Back</a>
<button onclick="window.print()" style="padding:6px 14px">Print</button> <a href="' . e(url('student/certificates/download.php?id=' . $c['id'])) . '" style="color:#fff;margin-left:10px">Download PDF</a></div>';
$html = str_replace('<body>', '<body>' . $bar, $html);
if (!empty($_GET['print'])) $html = str_replace('</body>', '<script>window.onload=()=>window.print()</script></body>', $html);
echo $html;
