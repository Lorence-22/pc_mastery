<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');
header('Content-Type: application/json');
$pdo = db();
$one = fn(string $sql) => $pdo->query($sql)->fetchColumn();

$cards = [
  'students'   => (int)$one("SELECT COUNT(*) FROM users WHERE role='student'"),
  'active'     => (int)$one("SELECT COUNT(*) FROM users WHERE role='student' AND status='active'"),
  'quizzes'    => (int)$one('SELECT COUNT(*) FROM quizzes'),
  'materials'  => (int)$one('SELECT (SELECT COUNT(*) FROM learning_materials)+(SELECT COUNT(*) FROM reviewer_topics)'),
  'assembly'   => (int)$one('SELECT COUNT(*) FROM assembly_modules'),
  'certs'      => (int)$one("SELECT COUNT(*) FROM certificates WHERE status='valid'"),
  'avg_score'  => round((float)$one("SELECT COALESCE(AVG(percentage),0) FROM quiz_attempts WHERE status IN ('submitted','graded')"), 1),
  'pass_rate'  => round((float)$one("SELECT COALESCE(100*SUM(passed=1)/NULLIF(COUNT(*),0),0) FROM quiz_attempts WHERE status IN ('submitted','graded')"), 1),
];

// 1. Student performance: avg quiz % per top 10 students
$perf = $pdo->query("SELECT CONCAT(sp.first_name,' ',sp.last_name) n, ROUND(AVG(a.percentage),1) v
    FROM quiz_attempts a JOIN student_profiles sp ON sp.user_id=a.user_id WHERE a.status IN ('submitted','graded')
    GROUP BY a.user_id, sp.first_name, sp.last_name ORDER BY v DESC LIMIT 10")->fetchAll();
// 2. Quiz results overview: avg per quiz
$quiz = $pdo->query("SELECT q.title n, ROUND(AVG(a.percentage),1) v FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id
    WHERE a.status IN ('submitted','graded') GROUP BY q.id ORDER BY q.title LIMIT 12")->fetchAll();
// 3. Monthly registrations (last 12 months)
$reg = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) v FROM users WHERE role='student'
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY m ORDER BY m")->fetchAll();
// 4. Passing vs failing students (best attempt per student across quizzes)
$pf = $pdo->query("SELECT COALESCE(SUM(passed=1),0) p, COALESCE(SUM(passed=0),0) f FROM quiz_attempts WHERE status IN ('submitted','graded')")->fetch();
// 5. Most difficult topics: lowest correct rate per question topic
$diff = $pdo->query("SELECT COALESCE(q.topic,'General') n, ROUND(100*SUM(an.is_correct=1)/COUNT(*),1) v
    FROM quiz_answers an JOIN quiz_questions q ON q.id=an.question_id WHERE an.is_correct IS NOT NULL
    GROUP BY q.topic ORDER BY v ASC LIMIT 6")->fetchAll();
// 6. Assembly performance: avg % by mode
$asm = $pdo->query("SELECT mode n, ROUND(AVG(percentage),1) v FROM assembly_attempts WHERE status='completed' GROUP BY mode")->fetchAll();

$recent = [
 'students' => $pdo->query("SELECT CONCAT(first_name,' ',last_name) t, created_at d FROM student_profiles ORDER BY created_at DESC LIMIT 5")->fetchAll(),
 'quizzes'  => $pdo->query("SELECT CONCAT(sp.first_name,' ',sp.last_name,' - ',q.title,' (',a.percentage,'%)') t, a.submitted_at d
    FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id JOIN student_profiles sp ON sp.user_id=a.user_id
    WHERE a.submitted_at IS NOT NULL ORDER BY a.submitted_at DESC LIMIT 5")->fetchAll(),
 'assembly' => $pdo->query("SELECT CONCAT(sp.first_name,' ',sp.last_name,' - ',a.mode,' (',a.percentage,'%)') t, a.completed_at d
    FROM assembly_attempts a JOIN student_profiles sp ON sp.user_id=a.user_id WHERE a.status='completed' ORDER BY a.completed_at DESC LIMIT 5")->fetchAll(),
 'certs'    => $pdo->query("SELECT CONCAT(sp.first_name,' ',sp.last_name,' - ',c.certificate_no) t, c.issued_at d
    FROM certificates c JOIN student_profiles sp ON sp.user_id=c.user_id ORDER BY c.issued_at DESC LIMIT 5")->fetchAll(),
];
echo json_encode(compact('cards','perf','quiz','reg','pf','diff','asm','recent'));
