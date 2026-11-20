<?php
// Secure file delivery: PDFs and reviewer files are never served directly by Apache.
require_once __DIR__ . '/../includes/functions.php';
$user = require_login();
$t = $_GET['t'] ?? ''; $id = (int)($_GET['id'] ?? 0); $dl = !empty($_GET['dl']);
$pdo = db(); $file = null; $dir = ''; $name = 'file';
if ($t === 'material') {
    $s = $pdo->prepare('SELECT * FROM learning_materials WHERE id=?'); $s->execute([$id]); $m = $s->fetch();
    if ($m) { if ($user['role'] !== 'admin' && $dl && !$m['downloadable']) { http_response_code(403); exit('Download not permitted.'); }
        $file = $m['file_name']; $dir = 'reviewers'; $name = $m['original_name']; }
} elseif ($t === 'quiz') {
    $s = $pdo->prepare('SELECT * FROM quizzes WHERE id=? AND pdf_file IS NOT NULL'); $s->execute([$id]); $q = $s->fetch();
    if ($q && ($user['role'] === 'admin' || $q['is_active'])) {
        if ($user['role'] === 'student') { // only while the quiz is open to the student
            $now = date('Y-m-d H:i:s'); if (($q['available_from'] && $now < $q['available_from']) || ($q['available_until'] && $now > $q['available_until'])) { http_response_code(403); exit('Quiz not available.'); }
            $dl = false; // students may view but not download quiz PDFs
        }
        $file = $q['pdf_file']; $dir = 'pdf'; $name = $q['title'] . '.pdf';
    }
}
if (!$file || !preg_match('/^[a-f0-9]{32}\.[a-z]{3,4}$/', $file) || !is_file($path = __DIR__ . "/../uploads/$dir/$file")) { http_response_code(404); exit('File not found.'); }
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($dl ? 'attachment' : 'inline') . '; filename="' . preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) . '"');
readfile($path);
