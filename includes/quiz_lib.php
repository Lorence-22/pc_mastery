<?php
function qnorm(string $s): string { return mb_strtolower(trim(preg_replace('/\s+/', ' ', $s))); }

function load_questions(int $quizId): array {
    $s = db()->prepare('SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY sort_order,id'); $s->execute([$quizId]); $qs = $s->fetchAll();
    $c = db()->prepare('SELECT * FROM quiz_choices WHERE question_id=? ORDER BY sort_order,id');
    foreach ($qs as &$q) { $c->execute([$q['id']]); $q['choices'] = $c->fetchAll(); } unset($q);
    return $qs;
}
/** Turn raw posted input into the string stored in quiz_answers.answer_text (null = unanswered). */
function normalize_answer(array $q, $raw): ?string {
    $ids = array_column($q['choices'], 'id');
    switch ($q['question_type']) {
        case 'multiple_choice': case 'true_false':
            return (is_scalar($raw) && in_array((int)$raw, array_map('intval', $ids), true)) ? (string)(int)$raw : null;
        case 'multiple_answer':
            if (!is_array($raw)) return null; $v = array_values(array_unique(array_filter(array_map('intval', $raw), fn($x) => in_array($x, array_map('intval', $ids), true)))); sort($v);
            return $v ? json_encode($v) : null;
        case 'identification': $v = is_scalar($raw) ? trim((string)$raw) : ''; return $v === '' ? null : mb_substr($v, 0, 500);
        case 'enumeration':
            $lines = is_scalar($raw) ? preg_split('/\R|,/', (string)$raw) : []; $lines = array_values(array_filter(array_map('trim', $lines), 'strlen'));
            return $lines ? json_encode(array_slice($lines, 0, 50)) : null;
        case 'matching':
            if (!is_array($raw)) return null; $m = []; foreach ($raw as $cid => $val) if (in_array((int)$cid, array_map('intval', $ids), true) && is_scalar($val) && trim((string)$val) !== '') $m[(int)$cid] = trim((string)$val);
            return $m ? json_encode($m) : null;
    }
    return null;
}
function save_posted_answers(int $attemptId, array $questions, array $posted): void {
    $up = db()->prepare('INSERT INTO quiz_answers (attempt_id,question_id,answer_text) VALUES (?,?,?) ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text)');
    $del = db()->prepare('DELETE FROM quiz_answers WHERE attempt_id=? AND question_id=?');
    foreach ($questions as $q) {
        $a = normalize_answer($q, $posted[$q['id']] ?? null);
        if ($a === null) $del->execute([$attemptId, $q['id']]); else $up->execute([$attemptId, $q['id'], $a]);
    }
}
/** returns [earned, is_correct, needs_review] */
function grade_question(array $q, ?string $ans): array {
    $pts = (float)$q['points']; if ($ans === null) return [0.0, 0, 0];
    switch ($q['question_type']) {
        case 'multiple_choice': case 'true_false':
            foreach ($q['choices'] as $c) if ((string)$c['id'] === $ans) return $c['is_correct'] ? [$pts, 1, 0] : [0.0, 0, 0]; return [0.0, 0, 0];
        case 'multiple_answer':
            $given = json_decode($ans, true) ?: []; $right = array_map('intval', array_column(array_filter($q['choices'], fn($c) => $c['is_correct']), 'id')); sort($given); sort($right);
            return $given == $right ? [$pts, 1, 0] : [0.0, 0, 0];
        case 'identification':
            $ok = in_array(qnorm($ans), array_map('qnorm', explode('|', (string)$q['correct_answer'])), true);
            return $ok ? [$pts, 1, 0] : [0.0, 0, 1];  // wrong ident answers are flagged so an admin can credit near-misses
        case 'enumeration':
            $exp = array_unique(array_map('qnorm', array_filter(explode('|', (string)$q['correct_answer']), 'strlen'))); $giv = array_unique(array_map('qnorm', json_decode($ans, true) ?: []));
            $hit = count(array_intersect($giv, $exp)); $n = max(1, count($exp));
            return [round($pts * $hit / $n, 2), $hit === count($exp) ? 1 : 0, $hit < count($exp) ? 1 : 0];
        case 'matching':
            $given = json_decode($ans, true) ?: []; $tot = 0; $hit = 0;
            foreach ($q['choices'] as $c) { if ($c['match_text'] === null || $c['match_text'] === '') continue; $tot++; if (isset($given[$c['id']]) && qnorm($given[$c['id']]) === qnorm($c['match_text'])) $hit++; }
            return [$tot ? round($pts * $hit / $tot, 2) : 0.0, ($tot && $hit === $tot) ? 1 : 0, 0];
    }
    return [0.0, 0, 0];
}
/** Final grading of a submitted attempt; PDF quizzes go to the admin review queue. */
function finalize_attempt(int $attemptId): void {
    $pdo = db();
    $a = $pdo->prepare('SELECT a.*, q.method, q.passing_score FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.id=?'); $a->execute([$attemptId]); $at = $a->fetch();
    if (!$at || $at['status'] !== 'in_progress') return;
    if ($at['method'] === 'pdf') {
        $pdo->prepare("UPDATE quiz_attempts SET submitted_at=NOW(), status='needs_review' WHERE id=?")->execute([$attemptId]);
        $admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN); foreach ($admins as $ad) notify((int)$ad, 'A PDF quiz answer needs review.', url('admin/quizzes/review.php?attempt=' . $attemptId));
        return;
    }
    $qs = load_questions((int)$at['quiz_id']); $ans = $pdo->prepare('SELECT question_id, answer_text FROM quiz_answers WHERE attempt_id=?'); $ans->execute([$attemptId]); $map = $ans->fetchAll(PDO::FETCH_KEY_PAIR);
    $up = $pdo->prepare('INSERT INTO quiz_answers (attempt_id,question_id,answer_text,is_correct,points_earned,needs_review) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE is_correct=VALUES(is_correct), points_earned=VALUES(points_earned), needs_review=VALUES(needs_review)');
    $score = 0; $total = 0;
    foreach ($qs as $q) { [$e, $ok, $nr] = grade_question($q, $map[$q['id']] ?? null); $score += $e; $total += (float)$q['points']; $up->execute([$attemptId, $q['id'], $map[$q['id']] ?? null, $ok, $e, $nr]); }
    $pct = $total > 0 ? round(100 * $score / $total, 2) : 0;
    $pdo->prepare("UPDATE quiz_attempts SET submitted_at=NOW(), score=?, total_points=?, percentage=?, passed=?, status='graded' WHERE id=?")->execute([$score, $total, $pct, $pct >= (float)$at['passing_score'] ? 1 : 0, $attemptId]);
}
/** Recompute an attempt's totals from quiz_answers (used after admin edits points). */
function recompute_attempt(int $attemptId): void {
    $pdo = db(); $a = $pdo->prepare('SELECT a.quiz_id, q.passing_score FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.id=?'); $a->execute([$attemptId]); $at = $a->fetch(); if (!$at) return;
    $t = $pdo->prepare('SELECT COALESCE(SUM(points),0) FROM quiz_questions WHERE quiz_id=?'); $t->execute([$at['quiz_id']]); $total = (float)$t->fetchColumn();
    $e = $pdo->prepare('SELECT COALESCE(SUM(points_earned),0) FROM quiz_answers WHERE attempt_id=?'); $e->execute([$attemptId]); $score = (float)$e->fetchColumn();
    $pct = $total > 0 ? round(100 * $score / $total, 2) : 0;
    $pdo->prepare("UPDATE quiz_attempts SET score=?,total_points=?,percentage=?,passed=?,status='graded' WHERE id=?")->execute([$score, $total, $pct, $pct >= (float)$at['passing_score'] ? 1 : 0, $attemptId]);
}
function quiz_open(array $q): ?string {
    $now = date('Y-m-d H:i:s');
    if (!$q['is_active']) return 'This quiz is not active.';
    if ($q['available_from'] && $now < $q['available_from']) return 'Opens on ' . fmt_dt($q['available_from']) . '.';
    if ($q['available_until'] && $now > $q['available_until']) return 'Closed on ' . fmt_dt($q['available_until']) . '.';
    return null;
}
function answer_display(array $q, ?string $ans): string {
    if ($ans === null) return '<i class="text-muted">No answer</i>';
    switch ($q['question_type']) {
        case 'multiple_choice': case 'true_false': foreach ($q['choices'] as $c) if ((string)$c['id'] === $ans) return e($c['choice_text']); return '-';
        case 'multiple_answer': $ids = json_decode($ans, true) ?: []; return e(implode(', ', array_map(fn($c) => $c['choice_text'], array_filter($q['choices'], fn($c) => in_array((int)$c['id'], $ids, true)))));
        case 'identification': return e($ans);
        case 'enumeration': return e(implode(', ', json_decode($ans, true) ?: []));
        case 'matching': $m = json_decode($ans, true) ?: []; $o = []; foreach ($q['choices'] as $c) $o[] = e($c['choice_text']) . ' &rarr; ' . e($m[$c['id']] ?? '?'); return implode('<br>', $o);
    }
    return '';
}
function correct_display(array $q): string {
    switch ($q['question_type']) {
        case 'multiple_choice': case 'true_false': case 'multiple_answer': return e(implode(', ', array_map(fn($c) => $c['choice_text'], array_filter($q['choices'], fn($c) => $c['is_correct']))));
        case 'identification': return e(str_replace('|', ' / ', (string)$q['correct_answer']));
        case 'enumeration': return e(str_replace('|', ', ', (string)$q['correct_answer']));
        case 'matching': return implode('<br>', array_map(fn($c) => e($c['choice_text']) . ' &rarr; ' . e($c['match_text']), $q['choices']));
    }
    return '';
}
const QTYPES = ['multiple_choice' => 'Multiple choice', 'multiple_answer' => 'Multiple correct answers', 'true_false' => 'True or false', 'identification' => 'Identification', 'enumeration' => 'Enumeration', 'matching' => 'Matching type'];
