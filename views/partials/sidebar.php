<?php
/** SIDEBAR: menu dibuat dari array supaya mudah menambah menu baru. [kunci, url, ikon, label] */
$menu = [
    'Ringkasan'  => [['dashboard', '/', 'bi-grid-1x2', 'Dashboard']],
    'Transaksi'  => [
        ['pos',       '/sales/create', 'bi-cart3',    'Kasir'],
        ['sales',     '/sales',        'bi-receipt',  'Penjualan'],
        ['purchases', '/purchases',    'bi-bag-plus', 'Pembelian'],
    ],
    'Data master' => [
        ['products',  '/products',  'bi-box-seam', 'Produk & stok'],
        ['customers', '/customers', 'bi-people',   'Pelanggan'],
        ['suppliers', '/suppliers', 'bi-truck',    'Supplier'],
    ],
    'Keuangan' => [
        ['cash',        '/cash',        'bi-wallet2',                'Kas'],
        ['receivables', '/receivables', 'bi-arrow-down-left-circle', 'Piutang'],
        ['payables',    '/payables',    'bi-arrow-up-right-circle',  'Hutang'],
        ['reports',     '/reports',     'bi-bar-chart-line',         'Laporan'],
    ],
];
$user = \App\Auth::user();
?>
<aside class="sidebar">
  <a class="brand" href="/">
    <span class="brand-mark"><i class="bi bi-shop"></i></span>
    <span><?= e($_ENV['APP_NAME'] ?? 'UMKM Toolkit') ?></span>
  </a>

  <nav class="sidebar-scroll">
    <?php foreach ($menu as $group => $links): ?>
      <div class="nav-group"><?= e($group) ?></div>
      <?php foreach ($links as [$key, $url, $icon, $label]): ?>
        <a href="<?= $url ?>" class="side-link <?= ($active ?? '') === $key ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i><?= e($label) ?>
          <?php if ($key === 'products' && low_stock_count() > 0): ?>
            <span class="badge rounded-pill text-bg-warning"><?= low_stock_count() ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-user">
    <div class="avatar"><?= e(initials($user['name'])) ?></div>
    <div class="min-w-0 flex-grow-1" style="min-width:0">
      <div class="small fw-semibold text-truncate"><?= e($user['name']) ?></div>
      <div class="small text-secondary text-truncate"><?= e($user['business']) ?></div>
    </div>
    <form method="POST" action="/logout">
      <?= csrf_field() ?>
      <button class="btn btn-soft btn-icon btn-sm" title="Keluar" aria-label="Keluar"><i class="bi bi-box-arrow-right"></i></button>
    </form>
  </div>
</aside>