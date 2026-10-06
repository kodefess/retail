<div class="page-head no-print">
  <div><h1>Laporan</h1><p><?= e(tgl($from)) ?> &ndash; <?= e(tgl($to)) ?></p></div>
  <div class="d-flex gap-2">
    <a class="btn btn-soft" href="/reports/export?from=<?= e($from) ?>&to=<?= e($to) ?>"><i class="bi bi-filetype-csv me-1"></i>Unduh CSV</a>
    <button class="btn btn-soft" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
  </div>
</div>

<div class="card mb-3 no-print">
  <div class="card-body">
    <form class="row g-2 align-items-end">
      <div class="col-6 col-md-auto"><label class="form-label">Dari</label><input type="date" name="from" value="<?= e($from) ?>" class="form-control num"></div>
      <div class="col-6 col-md-auto"><label class="form-label">Sampai</label><input type="date" name="to" value="<?= e($to) ?>" class="form-control num"></div>
      <div class="col-md-auto"><button class="btn btn-primary w-100">Tampilkan</button></div>
      <div class="col-md-auto d-flex gap-1 flex-wrap">
        <a class="btn btn-soft btn-sm" href="/reports?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">Hari ini</a>
        <a class="btn btn-soft btn-sm" href="/reports?from=<?= date('Y-m-d', strtotime('-6 days')) ?>&to=<?= date('Y-m-d') ?>">7 hari</a>
        <a class="btn btn-soft btn-sm" href="/reports?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>">Bulan ini</a>
        <a class="btn btn-soft btn-sm" href="/reports?from=<?= date('Y-m-01', strtotime('first day of last month')) ?>&to=<?= date('Y-m-t', strtotime('last month')) ?>">Bulan lalu</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ([
      ['Penjualan', $summary['sales'], 'bi-receipt', 'info'],
      ['Laba kotor', $summary['profit'], 'bi-graph-up-arrow', ''],
      ['Pembelian stok', $summary['purchases'], 'bi-bag', 'warn'],
      ['Kas bersih', $summary['net'], 'bi-wallet2', 'violet'],
  ] as [$l, $v, $i, $t]): ?>
    <div class="col-6 col-xl-3">
      <div class="card stat-card d-flex flex-row justify-content-between align-items-start">
        <div><div class="stat-label"><?= $l ?></div><div class="stat-value num"><?= money($v) ?></div></div>
        <div class="stat-icon <?= $t ?>"><i class="bi <?= $i ?>"></i></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h2 class="card-title">Penjualan harian</h2>
        <span class="small text-secondary"><span class="num"><?= $summary['count'] ?></span> transaksi &middot; rata-rata <span class="num"><?= money($summary['avg']) ?></span></span>
      </div>
      <div class="card-body">
        <?php if ($daily): ?>
          <div class="chart-box sm"><canvas id="reportChart" data-rows="<?= e(json_encode($daily)) ?>"></canvas></div>
        <?php else: ?><div class="empty"><i class="bi bi-bar-chart"></i>Belum ada penjualan di periode ini.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="card h-100">
      <div class="card-header"><h2 class="card-title">Produk terlaris</h2></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th class="ps-4">Produk</th><th class="text-end">Qty</th><th class="text-end">Omzet</th><th class="text-end pe-4">Laba</th></tr></thead>
          <tbody>
          <?php foreach ($topProducts as $p): ?>
            <tr><td class="ps-4"><?= e($p['name']) ?></td><td class="text-end num"><?= num($p['qty']) ?></td><td class="text-end num"><?= money($p['revenue']) ?></td><td class="text-end pe-4 num text-success"><?= money($p['profit']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$topProducts): ?><tr><td colspan="4"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-body">
    <div class="row text-center g-3">
      <div class="col-6"><div class="stat-label">Pemasukan kas</div><div class="fs-5 fw-semibold num text-success"><?= money($summary['income']) ?></div></div>
      <div class="col-6"><div class="stat-label">Pengeluaran kas</div><div class="fs-5 fw-semibold num text-danger"><?= money($summary['expense']) ?></div></div>
    </div>
  </div>
</div>