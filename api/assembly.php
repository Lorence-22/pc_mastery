<?php
// All assembly validation, scoring and certificate issuance happens here, server-side.
require_once __DIR__ . '/../includes/functions.php'; require_once __DIR__ . '/../includes/cert_lib.php';
$user = require_login('student'); $uid = $user['id']; $pdo = db();
header('Content-Type: application/json');
function out(array $d, int $code = 200): never { http_response_code($code); echo json_encode($d); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'POST only'], 405);
if (!csrf_verify()) out(['error' => 'Security token expired. Reload the page.'], 419);
$act = $_POST['action'] ?? '';
$attempt = function () use ($pdo, $uid) { $s = $pdo->prepare('SELECT a.*, m.passing_score, m.title mtitle FROM assembly_attempts a JOIN assembly_modules m ON m.id=a.module_id WHERE a.id=? AND a.user_id=?'); $s->execute([(int)($_POST['attempt_id'] ?? 0), $uid]); return $s->fetch() ?: out(['error' => 'Attempt not found'], 404); };
$stepRow = function (int $moduleId, int $no) use ($pdo) { $s = $pdo->prepare('SELECT * FROM assembly_steps WHERE module_id=? AND step_no=?'); $s->execute([$moduleId, $no]); return $s->fetch(); };

if ($act === 'start') {
    $mode = ($_POST['mode'] ?? '') === 'assessment' ? 'assessment' : 'practice'; $mid = (int)($_POST['module_id'] ?? 0);
    $m = $pdo->prepare('SELECT * FROM assembly_modules WHERE id=? AND is_active=1'); $m->execute([$mid]); if (!$m->fetch()) out(['error' => 'Module unavailable'], 404);
    $n = $pdo->prepare('SELECT COUNT(*) FROM assembly_steps WHERE module_id=?'); $n->execute([$mid]); if (!$n->fetchColumn()) out(['error' => 'This module has no steps yet.'], 400);
    $e = $pdo->prepare("SELECT id FROM assembly_attempts WHERE user_id=? AND module_id=? AND mode=? AND status='in_progress' LIMIT 1"); $e->execute([$uid, $mid, $mode]);
    if (!($id = $e->fetchColumn())) { $pdo->prepare('INSERT INTO assembly_attempts (module_id,user_id,mode,started_at) VALUES (?,?,?,NOW())')->execute([$mid, $uid, $mode]); $id = $pdo->lastInsertId(); log_activity('assembly_started', "$mode module $mid"); }
    out(['attempt_id' => (int)$id]);
}
$at = $attempt();
if ($act === 'restart') { if ($at['status'] === 'in_progress') $pdo->prepare('DELETE FROM assembly_attempts WHERE id=?')->execute([$at['id']]); out(['ok' => true]); }
$steps = $pdo->prepare('SELECT step_no,title,step_type,points FROM assembly_steps WHERE module_id=? ORDER BY step_no'); $steps->execute([$at['module_id']]); $steps = $steps->fetchAll(); $total = count($steps);

if ($act === 'state') {
    $placed = $pdo->prepare('SELECT s.step_no, d.zone_dropped zone, c.label, c.icon FROM assembly_attempt_details d JOIN assembly_steps s ON s.id=d.step_id LEFT JOIN assembly_components c ON c.id=d.component_id WHERE d.attempt_id=? AND d.is_correct=1 ORDER BY s.step_no'); $placed->execute([$at['id']]);
    $res = ['mode' => $at['mode'], 'status' => $at['status'], 'title' => $at['mtitle'], 'total' => $total, 'current' => (int)$at['current_step'], 'steps' => array_map(fn($s) => ['no' => (int)$s['step_no'], 'title' => $s['title']], $steps), 'placed' => $placed->fetchAll()];
    if ($at['status'] === 'in_progress') {
        $st = $stepRow((int)$at['module_id'], (int)$at['current_step']);
        $cs = $pdo->prepare('SELECT id,label,icon FROM assembly_components WHERE step_id=?'); $cs->execute([$st['id']]); $comps = $cs->fetchAll(); shuffle($comps);
        $res['step'] = ['no' => (int)$st['step_no'], 'title' => $st['title'], 'instruction' => $st['instruction'], 'type' => $st['step_type']]; $res['components'] = $comps;
    }
    out($res);
}
if ($at['status'] !== 'in_progress') out(['error' => 'Attempt already completed'], 400);
$st = $stepRow((int)$at['module_id'], (int)$at['current_step']); if (!$st) out(['error' => 'Step missing'], 500);

if ($act === 'hint') {
    if ($at['mode'] !== 'practice') out(['error' => 'Hints are disabled in assessment mode.'], 403);
    $_SESSION['hint'][$at['id']][$st['id']] = 1; out(['hint' => $st['hint'] ?: 'Re-read the instruction carefully.']);
}
if ($act === 'drop') {
    $cid = (int)($_POST['component_id'] ?? 0); $zone = substr(preg_replace('/[^a-z_]/', '', (string)($_POST['zone'] ?? '')), 0, 60);
    $c = $pdo->prepare('SELECT * FROM assembly_components WHERE id=? AND step_id=?'); $c->execute([$cid, $st['id']]); $comp = $c->fetch();
    if (!$comp) out(['error' => 'That component is not available for this step.'], 400);
    $practice = $at['mode'] === 'practice';
    $ok = $comp['is_correct'] && $comp['target_zone'] === $zone; $inSeq = $comp['target_zone'] === 'later' ? 0 : 1;
    $d = $pdo->prepare('SELECT * FROM assembly_attempt_details WHERE attempt_id=? AND step_id=?'); $d->execute([$at['id'], $st['id']]); $det = $d->fetch(); $tries = $det ? (int)$det['tries'] + 1 : 1;
    $hint = !empty($_SESSION['hint'][$at['id']][$st['id']]) ? 1 : 0;
    $pts = $ok ? round((float)$st['points'] * max(0, 1 - 0.5 * ($tries - 1)), 2) : 0;
    $seqFlag = ($det && !$det['in_sequence']) || !$inSeq ? 0 : 1;
    if ($det) $pdo->prepare('UPDATE assembly_attempt_details SET component_id=?, zone_dropped=?, is_correct=?, in_sequence=?, points_earned=?, hint_used=GREATEST(hint_used,?), tries=? WHERE id=?')->execute([$cid, $zone, $ok ? 1 : 0, $seqFlag, $pts, $hint, $tries, $det['id']]);
    else $pdo->prepare('INSERT INTO assembly_attempt_details (attempt_id,step_id,component_id,zone_dropped,is_correct,in_sequence,points_earned,hint_used,tries) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$at['id'], $st['id'], $cid, $zone, $ok ? 1 : 0, $seqFlag, $pts, $hint, $tries]);
    if (!$ok) {
        $msg = 'Incorrect placement. Try again.';
        if ($practice) $msg = $comp['target_zone'] === 'later' ? 'Right idea, wrong time: that part belongs to a later step. Follow the assembly sequence.' : ($comp['is_correct'] ? 'Correct component, but the wrong location. Think about where it is installed.' : 'That is not the correct component for this step.');
        out(['correct' => false, 'message' => $msg, 'tries' => $tries]);
    }
    $next = (int)$at['current_step'] + 1;
    if ($next <= $total) { $pdo->prepare('UPDATE assembly_attempts SET current_step=? WHERE id=?')->execute([$next, $at['id']]); out(['correct' => true, 'message' => $practice ? $st['explanation'] : 'Correct.', 'finished' => false, 'zone' => $zone, 'label' => $comp['label'], 'icon' => $comp['icon']]); }
    // ---- last step done: score the attempt ----
    $sum = $pdo->prepare('SELECT COALESCE(SUM(d.points_earned),0) e, COALESCE(SUM(s.points),0) t FROM assembly_attempt_details d JOIN assembly_steps s ON s.id=d.step_id WHERE d.attempt_id=?'); $sum->execute([$at['id']]); $r = $sum->fetch();
    $allPts = $pdo->prepare('SELECT COALESCE(SUM(points),0) FROM assembly_steps WHERE module_id=?'); $allPts->execute([$at['module_id']]); $all = (float)$allPts->fetchColumn();
    $pct = $all > 0 ? round(100 * (float)$r['e'] / $all, 2) : 0; $passed = $pct >= (float)$at['passing_score'] ? 1 : 0;
    $pdo->prepare("UPDATE assembly_attempts SET status='completed', completed_at=NOW(), score=?, total_points=?, percentage=?, passed=?, current_step=? WHERE id=?")->execute([$r['e'], $all, $pct, $passed, $total, $at['id']]);
    log_activity('assembly_completed', "{$at['mode']} {$pct}%"); $cert = null;
    if ($at['mode'] === 'assessment' && $passed) $cert = issue_certificate((int)$at['id']);
    out(['correct' => true, 'message' => $practice ? $st['explanation'] : 'Correct.', 'finished' => true, 'zone' => $zone, 'label' => $comp['label'], 'icon' => $comp['icon'], 'percentage' => $pct, 'passed' => (bool)$passed, 'certificate_id' => $cert]);
}
out(['error' => 'Unknown action'], 400);
