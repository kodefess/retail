<?php
/** LAYOUT AUTH: dua kolom (panel brand + form). Dipakai login, register, dan halaman error untuk tamu. */
$appName = $_ENV['APP_NAME'] ?? 'UMKM Toolkit';
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Masuk') ?> — <?= e($appName) ?></title>
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
  <div class="auth-wrap">
    <aside class="auth-side">
      <a class="brand text-white p-0" href="/">
        <span class="brand-mark"><i class="bi bi-shop"></i></span><?= e($appName) ?>
      </a>
      <div>
        <h2>Catat penjualan, stok, dan kas dalam satu tempat.</h2>
        <ul class="auth-points">
          <li><i class="bi bi-check2-circle"></i><span>Kasir cepat dengan scan barcode dan struk yang bisa dicetak</span></li>
          <li><i class="bi bi-check2-circle"></i><span>Stok berkurang otomatis dan kamu diberi tahu saat hampir habis</span></li>
          <li><i class="bi bi-check2-circle"></i><span>Piutang, hutang, dan laba bulan ini langsung terlihat</span></li>
        </ul>
      </div>
      <div class="auth-ticket">
        <div class="small opacity-75">Penjualan hari ini</div>
        <div class="num fs-4 fw-semibold">Rp 2.480.000</div>
        <div class="small" style="color:#5eead4"><i class="bi bi-graph-up-arrow me-1"></i>naik dari kemarin</div>
      </div>
    </aside>

    <main class="auth-main">
      <div class="auth-box">
        <?php if ($f = getFlash()): ?>
          <div class="alert alert-<?= e($f['t']) ?> py-2 small"><?= e($f['m']) ?></div>
        <?php endif; ?>
        <?php require $viewFile; ?>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>