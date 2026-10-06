<?php
/**
 * LAYOUT UTAMA: kerangka halaman setelah login.
 * Variabel dari controller yang dipakai: $title, $active (menu aktif), $viewFile (isi halaman), $scripts (JS tambahan)
 */
$appName = $_ENV['APP_NAME'] ?? 'UMKM Toolkit';
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Dashboard') ?> — <?= e($appName) ?></title>

  <!-- Terapkan tema SEBELUM halaman tampil, supaya tidak berkedip putih saat mode gelap -->
  <script>
    document.documentElement.setAttribute('data-bs-theme',
      localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="sidebar-backdrop"></div>

  <div class="main">
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <div class="content">
      <?php require $viewFile; ?>
    </div>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
  </div>

  <?php if ($f = getFlash()): ?>
    <?php $color = ['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'primary'][$f['t']] ?? 'primary'; ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
      <div class="toast align-items-center text-bg-<?= $color ?> border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body"><?= e($f['m']) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('js/app.js') ?>"></script>
  <?php foreach (($scripts ?? []) as $src): ?><script src="<?= e($src) ?>"></script><?php endforeach; ?>
</body>
</html>