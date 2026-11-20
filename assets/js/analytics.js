(function () {
  const d = window.AN, pal = ['#2563EB', '#06B6D4', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED', '#172554', '#EC4899'];
  const empty = id => { const c = document.getElementById(id); c.parentElement.innerHTML = '<div class="empty-state"><i class="fa fa-chart-simple"></i><p>No data</p></div>'; };
  const bar = (id, rows, label, horiz) => { if (!rows.length) return empty(id); new Chart(document.getElementById(id), { type: 'bar', data: { labels: rows.map(r => r.n), datasets: [{ label, data: rows.map(r => +r.v), backgroundColor: pal }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: horiz ? 'y' : 'x', plugins: { legend: { display: false } }, scales: { [horiz ? 'x' : 'y']: { beginAtZero: true, max: 100 } } } }); };
  bar('cStud', d.stud, 'Average %'); bar('cCat', d.cats, 'Average %'); bar('cQuiz', d.quiz, 'Pass rate %', true);
  if (d.pf[0] + d.pf[1] === 0) empty('cPF'); else new Chart(document.getElementById('cPF'), { type: 'doughnut', data: { labels: ['Passed', 'Failed'], datasets: [{ data: d.pf, backgroundColor: ['#16A34A', '#DC2626'] }] }, options: { responsive: true, maintainAspectRatio: false } });
  if (!d.trend.length) empty('cTrend'); else new Chart(document.getElementById('cTrend'), { type: 'line', data: { labels: d.trend.map(t => t.d), datasets: [{ label: 'Avg %', data: d.trend.map(t => +t.v), borderColor: '#2563EB', backgroundColor: 'rgba(37,99,235,.15)', fill: true, tension: .3 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } } });
})();
