<?php
// Autosave: stores current form state for an in-progress attempt (never grades).
require_once __DIR__ . '/../includes/functions.php'; require_once __DIR__ . '/../includes/quiz_lib.php';
$user = require_login('student'); header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) { http_response_code(419); exit(json_encode(['ok' => false])); }
$s = db()->prepare("SELECT a.*, z.time_limit_minutes, z.method FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE a.id=? AND a.user_id=? AND a.status='in_progress'"); $s->execute([(int)($_POST['attempt_id'] ?? 0), $user['id']]); $at = $s->fetch();
if (!$at || time() > strtotime($at['started_at']) + $at['time_limit_minutes'] * 60 + 30) exit(json_encode(['ok' => false]));
if ($at['method'] === 'pdf') db()->prepare('UPDATE quiz_attempts SET pdf_answer_text=? WHERE id=?')->execute([mb_substr(trim($_POST['pdf_answers'] ?? ''), 0, 20000), $at['id']]);
else save_posted_answers((int)$at['id'], load_questions((int)$at['quiz_id']), $_POST['q'] ?? []);
echo json_encode(['ok' => true, 'time' => date('H:i:s')]);
