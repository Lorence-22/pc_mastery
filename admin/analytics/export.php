<?php
require_once __DIR__ . '/../../includes/functions.php'; require_once __DIR__ . '/../../includes/analytics_lib.php'; require_once __DIR__ . '/../../includes/report_lib.php';
$user = require_login('admin'); $type = ($_GET['type'] ?? 'pdf') === 'excel' ? 'excel' : 'pdf';
if (!empty($_GET['student'])) {
    $R = student_report((int)$_GET['student']); if (!$R) { http_response_code(404); exit('Student not found.'); }
    $title = 'Report-' . preg_replace('/\W+/', '_', $R['info']['student_id']); $body = student_report_html($R);
} else {
    $D = analytics_data(analytics_filters()); $title = 'Analytics-' . date('Ymd');
    $body = '<h2>PC Mastery - Student Analytics</h2><p>Generated ' . date('F j, Y g:i A') . '</p><h3>Individual student performance</h3>' .
        html_table(['Student ID', 'Name', 'Course', 'Year', 'Section', 'Attempts', 'Avg %', 'High %', 'Low %', 'Pass rate %', 'Assembly best %', 'Materials done', 'Certificates'],
            array_map(fn($s) => [$s['student_id'], $s['name'], $s['course'], $s['year_level'], $s['section'], $s['attempts'], $s['avg'] ?? '-', $s['high'] ?? '-', $s['low'] ?? '-', $s['pass_rate'] ?? '-', $s['asm'] ?? '-', $s['done'], $s['certs']], $D['students'])) .
        '<h3>Quiz results</h3>' . html_table(['Quiz', 'Category', 'Attempts', 'Students', 'Completion %', 'Avg %', 'Pass rate %'], array_map(fn($z) => [$z['title'], $z['category'], $z['attempts'], $z['takers'], $z['completion'], $z['avg'] ?? '-', $z['pass_rate'] ?? '-'], $D['quizzes']));
}
log_activity('report_exported', "$type $title");
$html = report_page($title, $body);
if ($type === 'excel') { // HTML-table workbook: opens natively in Microsoft Excel and LibreOffice
    header('Content-Type: application/vnd.ms-excel; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $title . '.xls"'); echo "\xEF\xBB\xBF" . $html; exit;
}
render_pdf_or_print($html, $title);
