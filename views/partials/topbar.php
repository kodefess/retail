<header class="topbar">
  <button class="btn btn-soft btn-icon d-lg-none" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
  <div class="me-auto">
    <div class="fw-semibold lh-sm"><?= e($title ?? 'Dashboard') ?></div>
    <div class="small text-secondary d-none d-sm-block"><?= e(tgl(null, false, true)) ?></div>
  </div>
  <a href="/sales/create" class="btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center gap-1">
    <i class="bi bi-cart3"></i> Kasir
  </a>
  <button class="btn btn-soft btn-icon" data-theme-toggle aria-label="Ganti tema"><i class="bi bi-moon-stars"></i></button>
</header>