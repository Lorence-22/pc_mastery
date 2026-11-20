document.addEventListener('click', e => {
  const t = e.target.closest('.toggle-pw');
  if (t) {
    const i = t.parentElement.querySelector('input'); const show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    t.querySelector('i').className = 'fa ' + (show ? 'fa-eye-slash' : 'fa-eye');
  }
  if (e.target.closest('#sidebarToggle')) document.body.classList.toggle('sidebar-open');
  if (e.target.closest('#sidebarBackdrop')) document.body.classList.remove('sidebar-open');
  // Confirm-before-delete: <a|button data-confirm="message" data-href="..."> or inside a form
  const c = e.target.closest('[data-confirm]');
  if (c) {
    e.preventDefault();
    Swal.fire({ title: 'Are you sure?', text: c.dataset.confirm, icon: 'warning', showCancelButton: true,
      confirmButtonColor: '#DC2626', confirmButtonText: 'Yes, continue' }).then(r => {
      if (!r.isConfirmed) return;
      if (c.form) c.form.submit(); else if (c.href) location.href = c.href;
    });
  }
});
