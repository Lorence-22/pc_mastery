<?php
/** Full performance data for one student (used by admin view, analytics report, student progress page). */
function student_report(int $uid): array {
    $pdo = db();
    $q = function (string $sql, array $p = []) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); return $s; };
    $info = $q('SELECT u.id,u.username,u.email,u.status,u.last_login,u.created_at,sp.* FROM users u JOIN student_profiles sp ON sp.user_id=u.id WHERE u.id=?', [$uid])->fetch();
    if (!$info) return [];
    $quizzes = $q("SELECT a.*, z.title, z.category FROM quiz_attempts a JOIN quizzes z ON z.id=a.quiz_id WHERE a.user_id=? AND a.status<>'in_progress' ORDER BY a.submitted_at DESC", [$uid])->fetchAll();
    $assembly = $q("SELECT a.*, m.title FROM assembly_attempts a JOIN assembly_modules m ON m.id=a.module_id WHERE a.user_id=? AND a.status='completed' ORDER BY a.completed_at DESC", [$uid])->fetchAll();
    $certs = $q('SELECT * FROM certificates WHERE user_id=? ORDER BY issued_at DESC', [$uid])->fetchAll();
    $topics = $q("SELECT COALESCE(qq.topic,'General') topic, COUNT(*) total, SUM(an.is_correct=1) correct,
        ROUND(100*SUM(an.is_correct=1)/COUNT(*),1) pct FROM quiz_answers an JOIN quiz_attempts a ON a.id=an.attempt_id
        JOIN quiz_questions qq ON qq.id=an.question_id WHERE a.user_id=? AND an.is_correct IS NOT NULL GROUP BY qq.topic ORDER BY pct DESC", [$uid])->fetchAll();
    $total = (int)$pdo->query('SELECT (SELECT COUNT(*) FROM computer_parts)+(SELECT COUNT(*) FROM reviewer_topics)+(SELECT COUNT(*) FROM learning_materials)')->fetchColumn();
    $done = (int)$q('SELECT COUNT(*) FROM material_progress WHERE user_id=?', [$uid])->fetchColumn();
    $pcts = array_map(fn($r) => (float)$r['percentage'], $quizzes);
    $improvement = count($pcts) >= 2 ? round($pcts[0] - end($pcts), 1) : 0; // newest minus oldest
    return [
        'info' => $info, 'quizzes' => $quizzes, 'assembly' => $assembly, 'certs' => $certs, 'topics' => $topics,
        'strengths' => array_values(array_filter($topics, fn($t) => $t['pct'] >= 80)),
        'weaknesses' => array_values(array_filter($topics, fn($t) => $t['pct'] < 60)),
        'progress_total' => $total, 'progress_done' => $done, 'progress' => $total ? min(100, round(100 * $done / $total)) : 0,
        'avg' => $pcts ? round(array_sum($pcts) / count($pcts), 1) : 0, 'high' => $pcts ? max($pcts) : 0, 'low' => $pcts ? min($pcts) : 0,
        'attempts' => count($quizzes), 'improvement' => $improvement,
    ];
}
