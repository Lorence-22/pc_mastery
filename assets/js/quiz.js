(function () {
  const form = document.getElementById('quizForm'), tEl = document.getElementById('timer'), st = document.getElementById('saveState');
  let left = window.QUIZ.remaining, submitting = false;
  const fmt = s => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
  function submit() { if (submitting) return; submitting = true; window.onbeforeunload = null; form.submit(); }
  tEl.textContent = fmt(left);
  setInterval(() => { left--; tEl.textContent = fmt(Math.max(0, left)); if (left === 60) Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'One minute left!', timer: 4000, showConfirmButton: false });
    if (left <= 0) { Swal.fire({ title: "Time's up!", text: 'Submitting your answers...', allowOutsideClick: false, showConfirmButton: false }); submit(); } }, 1000);
  let timer = null;
  async function save() {
    const fd = new FormData(form); fd.append('attempt_id', window.QUIZ.attempt); st.textContent = 'Saving...';
    try { const r = await fetch(APP.base + '/api/quiz_save.php', { method: 'POST', body: fd, credentials: 'same-origin' }); const j = await r.json(); st.textContent = j.ok ? 'Saved ' + j.time : 'Not saved'; } catch (e) { st.textContent = 'Offline - will retry'; }
  }
  form.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(save, 800); });
  form.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(save, 400); });
  setInterval(save, 30000);
  document.getElementById('submitBtn').addEventListener('click', () => Swal.fire({ title: 'Submit your answers?', text: 'You cannot change them afterwards.', icon: 'question', showCancelButton: true, confirmButtonText: 'Submit' }).then(r => r.isConfirmed && submit()));
  window.onbeforeunload = () => 'Your quiz is in progress.';
})();
