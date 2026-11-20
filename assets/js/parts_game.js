(function () {
  const all = window.PARTS.slice(); const total = Math.min(10, all.length); if (total < 4) { document.querySelector('.card').innerHTML = '<p>Need at least 4 parts to play.</p>'; return; }
  const shuffle = a => a.map(v => [Math.random(), v]).sort((x, y) => x[0] - y[0]).map(x => x[1]);
  const rounds = shuffle(all).slice(0, total); let i = 0, score = 0, locked = false;
  const $ = id => document.getElementById(id); $('qt').textContent = total;
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  function show() {
    locked = false; const p = rounds[i]; $('qn').textContent = i + 1; $('bar').style.width = (i / total * 100) + '%'; $('fb').innerHTML = '';
    $('pic').innerHTML = p.image ? `<img src="${esc(p.image)}" style="max-height:160px;border-radius:12px">` : `<i class="fa ${esc(p.icon || 'fa-microchip')}"></i>`;
    $('clue').textContent = 'Hint: ' + (p.function_text || '');
    const opts = shuffle([p, ...shuffle(all.filter(x => x.id !== p.id)).slice(0, 3)]);
    $('opts').innerHTML = opts.map(o => `<button class="btn btn-outline-primary btn-lg" data-id="${o.id}">${esc(o.name)}</button>`).join('');
  }
  $('opts').addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b || locked) return; locked = true;
    const ok = +b.dataset.id === rounds[i].id; if (ok) score++; $('sc').textContent = score;
    b.className = 'btn btn-lg ' + (ok ? 'btn-success' : 'btn-danger');
    if (!ok) $('opts').querySelector(`[data-id="${rounds[i].id}"]`).className = 'btn btn-lg btn-success';
    $('fb').innerHTML = `<div class="alert alert-${ok ? 'success' : 'warning'} py-2">${ok ? 'Correct!' : 'It was ' + esc(rounds[i].name) + '.'}</div><button class="btn btn-primary" id="nx">${i + 1 < total ? 'Next' : 'Finish'}</button>`;
    $('nx').onclick = () => { i++; if (i < total) show(); else { $('bar').style.width = '100%'; document.querySelector('.card').innerHTML = `<h3>Game over!</h3><p class="fs-4">You scored ${score} / ${total}</p><a class="btn btn-primary" href="game.php">Play again</a> <a class="btn btn-outline-secondary" href="index.php">Back to lessons</a>`; } };
  });
  show();
})();
