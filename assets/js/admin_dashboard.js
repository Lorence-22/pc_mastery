(async function () {
  const res = await fetch(APP.base + '/api/admin_stats.php', { credentials: 'same-origin' });
  if (!res.ok) { Swal.fire('Error', 'Could not load statistics.', 'error'); return; }
  const d = await res.json();
  document.querySelectorAll('[data-stat]').forEach(el => el.textContent = d.cards[el.dataset.stat]);

  const pal = ['#2563EB', '#06B6D4', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED', '#172554', '#EC4899'];
  const empty = (id) => { const c = document.getElementById(id); c.parentElement.innerHTML = '<div class="empty-state"><i class="fa fa-chart-simple"></i><p>No data yet</p></div>'; };
  const make = (id, type, rows, label, opts = {}) => {
    if (!rows.length) return empty(id);
    new Chart(document.getElementById(id), {
      type, data: { labels: rows.map(r => r.n ?? r.m), datasets: [{ label, data: rows.map(r => +r.v), backgroundColor: type === 'line' ? 'rgba(37,99,235,.15)' : pal, borderColor: '#2563EB', fill: type === 'line', tension: .35 }] },
      options: Object.assign({ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: type === 'doughnut' } }, scales: type === 'doughnut' ? {} : { y: { beginAtZero: true, max: opts.max } } }, opts.extra || {})
    });
  };
  make('chPerf', 'bar', d.perf, 'Average %', { max: 100 });
  make('chQuiz', 'bar', d.quiz, 'Average %', { max: 100 });
  make('chReg', 'line', d.reg, 'Registrations');
  make('chDiff', 'bar', d.diff, '% correct', { max: 100, extra: { indexAxis: 'y' } });
  make('chAsm', 'bar', d.asm, 'Average %', { max: 100 });
  const pf = d.pf;
  if (+pf.p + +pf.f === 0) empty('chPF');
  else new Chart(document.getElementById('chPF'), { type: 'doughnut',
    data: { labels: ['Passed', 'Failed'], datasets: [{ data: [+pf.p, +pf.f], backgroundColor: ['#16A34A', '#DC2626'] }] },
    options: { responsive: true, maintainAspectRatio: false } });

  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  document.querySelectorAll('[data-recent]').forEach(ul => {
    const rows = d.recent[ul.dataset.recent];
    ul.innerHTML = rows.length ? rows.map(r => `<li class="d-flex justify-content-between border-bottom py-1"><span>${esc(r.t)}</span><small class="text-muted">${esc(r.d)}</small></li>`).join('')
      : '<li class="text-muted">Nothing yet</li>';
  });
})();
