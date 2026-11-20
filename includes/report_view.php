<?php /** expects $R = student_report() result */ $i = $R['info']; ?>
<div class="row g-3 mb-3">
  <div class="col-lg-4"><div class="card p-3 h-100"><h6>Student Information</h6>
    <dl class="row mb-0 small"><dt class="col-5">Student ID</dt><dd class="col-7"><?= e($i['student_id']) ?></dd>
    <dt class="col-5">Name</dt><dd class="col-7"><?= e("{$i['first_name']} {$i['middle_name']} {$i['last_name']}") ?></dd>
    <dt class="col-5">Course</dt><dd class="col-7"><?= e($i['course']) ?></dd><dt class="col-5">Year / Section</dt><dd class="col-7"><?= e($i['year_level'] . ' / ' . $i['section']) ?></dd>
    <dt class="col-5">Email</dt><dd class="col-7"><?= e($i['email']) ?></dd><dt class="col-5">Last login</dt><dd class="col-7"><?= fmt_dt($i['last_login']) ?></dd></dl></div></div>
  <div class="col-lg-8"><div class="row g-3 h-100">
  <?php foreach ([['Quiz Attempts', $R['attempts']], ['Average', $R['avg'] . '%'], ['Highest / Lowest', $R['high'] . ' / ' . $R['low']], ['Improvement', ($R['improvement'] > 0 ? '+' : '') . $R['improvement'] . '%'], ['Learning Progress', $R['progress'] . '%'], ['Certificates', count($R['certs'])]] as [$l, $v]): ?>
    <div class="col-6 col-md-4"><div class="stat-card"><div><div class="stat-value fs-4"><?= e($v) ?></div><div class="stat-label"><?= $l ?></div></div></div></div><?php endforeach; ?></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card p-3 h-100"><h6 class="text-success"><i class="fa fa-thumbs-up"></i> Strengths (80%+ correct)</h6><?php foreach ($R['strengths'] as $t): ?><div class="d-flex justify-content-between"><span><?= e($t['topic']) ?></span><b><?= $t['pct'] ?>%</b></div><?php endforeach; if (!$R['strengths']) echo '<small class="text-muted">Not enough data yet.</small>'; ?></div></div>
  <div class="col-md-6"><div class="card p-3 h-100"><h6 class="text-danger"><i class="fa fa-triangle-exclamation"></i> Needs improvement (below 60%)</h6><?php foreach ($R['weaknesses'] as $t): ?><div class="d-flex justify-content-between"><span><?= e($t['topic']) ?></span><b><?= $t['pct'] ?>%</b></div><?php endforeach; if (!$R['weaknesses']) echo '<small class="text-muted">None identified.</small>'; ?></div></div>
</div>
<div class="card p-3 mb-3"><h6>Quiz History</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Quiz</th><th>Category</th><th>Attempt</th><th>Score</th><th>Result</th><th>Date</th></tr></thead><tbody>
<?php foreach ($R['quizzes'] as $q): ?><tr><td><?= e($q['title']) ?></td><td><?= e($q['category']) ?></td><td>#<?= $q['attempt_no'] ?></td><td><?= $q['percentage'] === null ? 'Pending review' : e($q['percentage']) . '%' ?></td><td><?= $q['passed'] === null ? '-' : ($q['passed'] ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>') ?></td><td><?= fmt_dt($q['submitted_at']) ?></td></tr>
<?php endforeach; if (!$R['quizzes']) echo '<tr><td colspan="6" class="text-muted">No quizzes taken.</td></tr>'; ?></tbody></table></div></div>
<div class="card p-3 mb-3"><h6>Assembly Assessment History</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Module</th><th>Mode</th><th>Score</th><th>Result</th><th>Date</th></tr></thead><tbody>
<?php foreach ($R['assembly'] as $a): ?><tr><td><?= e($a['title']) ?></td><td class="text-capitalize"><?= e($a['mode']) ?></td><td><?= e($a['percentage']) ?>%</td><td><?= $a['passed'] ? '<span class="badge text-bg-success">Passed</span>' : '<span class="badge text-bg-danger">Failed</span>' ?></td><td><?= fmt_dt($a['completed_at']) ?></td></tr>
<?php endforeach; if (!$R['assembly']) echo '<tr><td colspan="5" class="text-muted">No assembly attempts.</td></tr>'; ?></tbody></table></div></div>
<div class="card p-3"><h6>Certificates</h6><?php foreach ($R['certs'] as $c): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?= e($c['certificate_no']) ?> - <?= e($c['title']) ?></span><span><span class="badge text-bg-<?= $c['status'] === 'valid' ? 'success' : 'danger' ?>"><?= e($c['status']) ?></span> <?= fmt_dt($c['issued_at']) ?></span></div><?php endforeach; if (!$R['certs']) echo '<small class="text-muted">No certificates earned.</small>'; ?></div>
