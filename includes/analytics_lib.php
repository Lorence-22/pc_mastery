<?php
function analytics_filters(): array {
    $f = []; foreach (['q', 'course', 'year_level', 'section', 'category', 'from', 'to'] as $k) $f[$k] = trim($_GET[$k] ?? ''); return $f;
}
/** Student-side WHERE (on sp) */
function an_student_where(array $f): array {
    $w = ['1=1']; $p = [];
    if ($f['q'] !== '') { $w[] = '(sp.first_name LIKE ? OR sp.last_name LIKE ? OR sp.student_id LIKE ? OR CONCAT(sp.first_name," ",sp.last_name) LIKE ?)'; array_push($p, ...array_fill(0, 4, '%' . $f['q'] . '%')); }
    foreach (['course', 'year_level', 'section'] as $k) if ($f[$k] !== '') { $w[] = "sp.$k=?"; $p[] = $f[$k]; }
    return [implode(' AND ', $w), $p];
}
/** Attempt-side WHERE (on a, z) */
function an_attempt_where(array $f): array {
    $w = ["a.status IN ('submitted','graded')"]; $p = [];
    if ($f['category'] !== '') { $w[] = 'z.category=?'; $p[] = $f['category']; }
    if ($f['from'] !== '') { $w[] = 'DATE(a.submitted_at)>=?'; $p[] = $f['from']; }
    if ($f['to'] !== '') { $w[] = 'DATE(a.submitted_at)<=?'; $p[] = $f['to']; }
    return [implode(' AND ', $w), $p];
}
function analytics_data(array $f): array {
    $pdo = db(); [$sw, $sp] = an_student_where($f); [$aw, $ap] = an_attempt_where($f);
    $q = function (string $sql, array $p) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); };
    $students = $q("SELECT sp.user_id, sp.student_id, CONCAT(sp.last_name,', ',sp.first_name) name, sp.course, sp.year_level, sp.section,
        COUNT(a.id) attempts, ROUND(AVG(a.percentage),1) avg, MAX(a.percentage) high, MIN(a.percentage) low, ROUND(100*SUM(a.passed=1)/NULLIF(COUNT(a.id),0),1) pass_rate,
        (SELECT ROUND(MAX(percentage),1) FROM assembly_attempts WHERE user_id=sp.user_id AND status='completed') asm,
        (SELECT COUNT(*) FROM material_progress WHERE user_id=sp.user_id) done,
        (SELECT COUNT(*) FROM certificates WHERE user_id=sp.user_id AND status='valid') certs
        FROM student_profiles sp LEFT JOIN (SELECT a.* FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE $aw) a ON a.user_id=sp.user_id
        WHERE $sw GROUP BY sp.user_id, sp.student_id, sp.first_name, sp.last_name, sp.course, sp.year_level, sp.section ORDER BY sp.last_name, sp.first_name", array_merge($ap, $sp));
    $base = "FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id JOIN student_profiles sp ON sp.user_id=a.user_id WHERE $aw AND $sw"; $bp = array_merge($ap, $sp);
    $nStud = count($students);
    $quizzes = $q("SELECT z.id, z.title, z.category, COUNT(a.id) attempts, COUNT(DISTINCT a.user_id) takers, ROUND(AVG(a.percentage),1) avg, ROUND(100*SUM(a.passed=1)/NULLIF(COUNT(a.id),0),1) pass_rate $base GROUP BY z.id, z.title, z.category ORDER BY z.title", $bp);
    foreach ($quizzes as &$z) $z['completion'] = $nStud ? round(100 * $z['takers'] / $nStud, 1) : 0; unset($z);
    $cats = $q("SELECT z.category n, ROUND(AVG(a.percentage),1) v $base GROUP BY z.category ORDER BY z.category", $bp);
    $trend = $q("SELECT DATE(a.submitted_at) d, ROUND(AVG(a.percentage),1) v, COUNT(*) c $base GROUP BY DATE(a.submitted_at) ORDER BY d", $bp);
    $sum = $q("SELECT COUNT(*) attempts, ROUND(AVG(a.percentage),1) avg, MAX(a.percentage) high, MIN(a.percentage) low, COALESCE(SUM(a.passed=1),0) passed, COALESCE(SUM(a.passed=0),0) failed $base", $bp)[0];
    $asm = $q("SELECT ROUND(AVG(x.percentage),1) avg, COUNT(*) n, COALESCE(SUM(x.passed=1),0) passed FROM assembly_attempts x JOIN student_profiles sp ON sp.user_id=x.user_id WHERE x.status='completed' AND $sw", $sp)[0];
    return compact('students', 'quizzes', 'cats', 'trend', 'sum', 'asm');
}
function render_pdf_or_print(string $html, string $filename): never {
    $auto = __DIR__ . '/../vendor/autoload.php';
    if (is_file($auto)) { require_once $auto; $o = new Dompdf\Options(); $o->set('isRemoteEnabled', false); $o->set('defaultFont', 'DejaVu Sans'); $d = new Dompdf\Dompdf($o); $d->loadHtml($html); $d->setPaper('A4', 'landscape'); $d->render(); $d->stream($filename . '.pdf', ['Attachment' => true]); exit; }
    echo str_replace('</body>', '<script>window.onload=()=>window.print()</script></body>', $html); exit;   // fallback: browser "Save as PDF"
}
function html_table(array $head, array $rows): string {
    $h = '<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%;font-size:11px"><tr style="background:#172554;color:#fff">';
    foreach ($head as $c) $h .= '<th>' . e($c) . '</th>'; $h .= '</tr>';
    foreach ($rows as $r) { $h .= '<tr>'; foreach ($r as $c) $h .= '<td>' . e($c) . '</td>'; $h .= '</tr>'; } return $h . '</table>';
}
function student_report_html(array $R): string {
    $i = $R['info']; $h = '<h2>Student Performance Report</h2><p><b>' . e($i['first_name'] . ' ' . $i['last_name']) . '</b> (' . e($i['student_id']) . ') &middot; ' . e($i['course'] . ', ' . $i['year_level'] . ' - ' . $i['section']) . '<br>Generated ' . date('F j, Y g:i A') . '</p>';
    $h .= '<p>Quiz attempts: <b>' . $R['attempts'] . '</b> | Average: <b>' . $R['avg'] . '%</b> | Highest: <b>' . $R['high'] . '%</b> | Lowest: <b>' . $R['low'] . '%</b> | Improvement (latest vs first): <b>' . $R['improvement'] . '%</b> | Learning progress: <b>' . $R['progress'] . '%</b> | Certificates: <b>' . count($R['certs']) . '</b></p>';
    $h .= '<h3>Strengths</h3><p>' . e(implode(', ', array_map(fn($t) => $t['topic'] . ' (' . $t['pct'] . '%)', $R['strengths'])) ?: 'Not enough data') . '</p>';
    $h .= '<h3>Areas that need improvement</h3><p>' . e(implode(', ', array_map(fn($t) => $t['topic'] . ' (' . $t['pct'] . '%)', $R['weaknesses'])) ?: 'None identified') . '</p>';
    $h .= '<h3>Quiz history</h3>' . html_table(['Quiz', 'Category', 'Attempt', 'Score %', 'Result', 'Date'], array_map(fn($q) => [$q['title'], $q['category'], '#' . $q['attempt_no'], $q['percentage'] ?? 'Pending', $q['passed'] === null ? '-' : ($q['passed'] ? 'Passed' : 'Failed'), $q['submitted_at']], $R['quizzes']));
    $h .= '<h3>Assembly assessment history</h3>' . html_table(['Module', 'Mode', 'Score %', 'Result', 'Date'], array_map(fn($a) => [$a['title'], $a['mode'], $a['percentage'], $a['passed'] ? 'Passed' : 'Failed', $a['completed_at']], $R['assembly']));
    $h .= '<h3>Certificates</h3>' . html_table(['Number', 'Title', 'Score %', 'Status', 'Issued'], array_map(fn($c) => [$c['certificate_no'], $c['title'], $c['score'], $c['status'], $c['issued_at']], $R['certs']));
    return $h;
}
function report_page(string $title, string $body): string {
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . e($title) . '</title><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;color:#0f172a}h2{color:#172554}h3{color:#2563EB;margin-bottom:4px}@page{size:A4 landscape;margin:12mm}</style></head><body>' . $body . '</body></html>';
}
