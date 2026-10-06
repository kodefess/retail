/* ==========================================================================
   app.js - perilaku umum yang dipakai di semua halaman
   1) tema gelap/terang  2) sidebar mobile  3) toast  4) modal dinamis
   5) konfirmasi hapus   6) tampil/sembunyi password
   ========================================================================== */
(() => {
  const html = document.documentElement;

  // 1) Tema: simpan pilihan di localStorage, beritahu halaman lain lewat event 'themechange'
  const themeIcon = () => {
    document.querySelectorAll('[data-theme-toggle] i').forEach(i => {
      i.className = 'bi ' + (html.dataset.bsTheme === 'dark' ? 'bi-sun' : 'bi-moon-stars');
    });
  };
  themeIcon();
  document.addEventListener('click', e => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const next = html.dataset.bsTheme === 'dark' ? 'light' : 'dark';
    html.dataset.bsTheme = next;
    localStorage.setItem('theme', next);
    themeIcon();
    document.dispatchEvent(new Event('themechange'));
  });

  // 2) Sidebar mobile
  document.addEventListener('click', e => {
    if (e.target.closest('[data-sidebar-toggle]')) document.body.classList.toggle('sidebar-open');
    else if (e.target.closest('.sidebar-backdrop')) document.body.classList.remove('sidebar-open');
  });

  // 3) Toast dari flash message
  document.querySelectorAll('.toast').forEach(el => new bootstrap.Toast(el, { delay: 4500 }).show());

  // 4) Modal dinamis: tombol dengan data-action & data-fill mengisi form di dalam modal.
  //    Contoh: <button data-bs-toggle="modal" data-bs-target="#m" data-action="/x/1/update" data-fill='{"name":"Budi"}'>
  document.addEventListener('show.bs.modal', e => {
    const trigger = e.relatedTarget, form = e.target.querySelector('form');
    if (!trigger || !form) return;
    form.reset();
    if (trigger.dataset.action) form.action = trigger.dataset.action;
    const title = e.target.querySelector('[data-modal-title]');
    if (title && trigger.dataset.title) title.textContent = trigger.dataset.title;
    if (trigger.dataset.fill) {
      const data = JSON.parse(trigger.dataset.fill);
      Object.entries(data).forEach(([k, v]) => {
        const f = form.elements[k];
        if (f) f.value = v ?? '';
        const max = form.querySelector('[data-max-from="' + k + '"]');
        if (max) max.max = v;
      });
    }
  });

  // 5) Konfirmasi sebelum aksi berbahaya: <form data-confirm="Hapus data ini?">
  document.addEventListener('submit', e => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });

  // 6) Tampil/sembunyi password: <button data-toggle-password="#inputId">
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    const input = document.querySelector(btn.dataset.togglePassword);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = 'bi ' + (input.type === 'password' ? 'bi-eye' : 'bi-eye-slash');
  });
})();