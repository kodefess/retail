<?php
/** Kartu statistik: [label, nilai, ikon, kelas warna, format] */
$cards = [
    ['Penjualan hari ini', $stats['today'],       'bi-receipt',                'info',   'money'],
    ['Laba bulan ini',     $stats['profit'],      'bi-graph-up-arrow',         '',       'money'],
    ['Saldo kas',          $stats['cash'],        'bi-wallet2',                'violet', 'money'],
    ['Piutang',            $stats['receivables'], 'bi-arrow-down-left-circle', 'warn',   'money'],
    ['Hutang',             $stats['payables'],    'bi-arrow-up-right-circle',  'danger', 'money'],
    ['Nilai persediaan',   $stats['stock_value'], 'bi-boxes',                  '',       'money'],
];
?>
<div class="page-head">
  <div>
    <h1><?= e($greeting) ?>, <?= e(explode(' ', \App\Auth::user()['name'])[0]) ?></h1>
    <p>Ini kondisi usahamu hari ini.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="/purchases/create" class="btn btn-soft"><i class="bi bi-bag-plus me-1"></i>Beli stok</a>
    <a href="/sales/create" class="btn btn-primary"><i class="bi bi-cart3 me-1"></i>Buka kasir</a>
  </div>
</div>

<!-- Kartu statistik -->
<div class="row g-3 mb-3">
  <?php foreach ($cards as [$label, $value, $icon, $tone, $fmt]): ?>
    <div class="col-6 col-xl-4">
      <div class="card stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div class="min-w-0">
            <div class="stat-label"><?= e($label) ?></div>
            <div class="stat-value num"><?= money($value) ?></div>
            <?php if ($label === 'Penjualan hari ini' && $stats['trend'] !== null): ?>
              <div class="trend mt-1 <?= $stats['trend'] >= 0 ? 'up' : 'down' ?>">
                <i class="bi bi-arrow-<?= $stats['trend'] >= 0 ? 'up' : 'down' ?>-short"></i><?= abs($stats['trend']) ?>% dari kemarin
              </div>
            <?php endif; ?>
          </div>
          <div class="stat-icon <?= $tone ?>"><i class="bi <?= $icon ?>"></i></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Chart utama -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="card-title">Penjualan & laba</h2>
          <div class="small text-secondary mt-1">
            <span class="num" id="rangeSales">-</span> penjualan &middot; <span class="num" id="rangeProfit">-</span> laba
          </div>
        </div>
        <div class="segmented" role="group" aria-label="Rentang waktu">
          <button type="button" class="active" data-range="7">7 hari</button>
          <button type="button" data-range="30">30 hari</button>
          <button type="button" data-range="90">90 hari</button>
        </div>
      </div>
      <div class="card-body"><div class="chart-box loading"><canvas id="salesChart"></canvas></div></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header"><h2 class="card-title">Produk terlaris</h2></div>
      <div class="card-body">
        <div class="chart-box loading"><canvas id="topChart"></canvas></div>
        <div id="topEmpty" class="empty d-none"><i class="bi bi-pie-chart"></i>Belum ada penjualan di periode ini.</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-5">
    <div class="card h-100">
      <div class="card-header"><h2 class="card-title">Arus kas</h2></div>
      <div class="card-body"><div class="chart-box sm loading"><canvas id="cashChart"></canvas></div></div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title">Stok menipis</h2>
        <a href="/products?filter=low" class="small text-decoration-none">Lihat semua</a>
      </div>
      <?php foreach ($lowStock as $p): ?>
        <?php $pct = $p['min_stock'] > 0 ? min(100, round($p['stock'] / $p['min_stock'] * 100)) : 0; ?>
        <div class="px-3 py-2 border-bottom">
          <div class="d-flex justify-content-between small">
            <span class="fw-medium text-truncate me-2"><?= e($p['name']) ?></span>
            <span class="num"><?= $p['stock'] ?> <?= e($p['unit']) ?></span>
          </div>
          <div class="stock-bar low mt-1"><span style="width:<?= $pct ?>%"></span></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$lowStock): ?><div class="empty"><i class="bi bi-check2-circle"></i>Semua stok aman.</div><?php endif; ?>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title">Penjualan terakhir</h2>
        <a href="/sales" class="small text-decoration-none">Lihat semua</a>
      </div>
      <?php foreach ($recentSales as $s): ?>
        <a href="/sales/<?= $s['id'] ?>" class="d-flex justify-content-between px-3 py-2 border-bottom text-decoration-none text-reset">
          <div class="min-w-0">
            <div class="small fw-medium num"><?= e($s['invoice_no']) ?></div>
            <div class="small text-secondary text-truncate"><?= e($s['customer_name'] ?? 'Umum') ?></div>
          </div>
          <div class="text-end">
            <div class="small fw-semibold num"><?= money($s['total']) ?></div>
            <div class="small text-secondary"><?= date('H:i', strtotime($s['sale_date'])) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (!$recentSales): ?><div class="empty"><i class="bi bi-receipt"></i>Belum ada penjualan.</div><?php endif; ?>
    </div>
  </div>
</div>