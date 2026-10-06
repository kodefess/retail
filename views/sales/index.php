<div class="page-head">
  <div><h1>Penjualan</h1><p><?= count($sales) ?> transaksi &middot; total <span class="num"><?= money($total) ?></span></p></div>
  <a href="/sales/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Transaksi baru</a>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <form class="row g-2 align-items-end">
      <div class="col-md"><div class="input-icon"><i class="bi bi-search"></i><input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Cari invoice atau pelanggan"></div></div>
      <div class="col-6 col-md-auto"><input type="date" class="form-control num" name="from" value="<?= e($from) ?>"></div>
      <div class="col-6 col-md-auto"><input type="date" class="form-control num" name="to" value="<?= e($to) ?>"></div>
      <div class="col-md-auto"><button class="btn btn-soft w-100">Terapkan</button></div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th class="ps-4">Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Metode</th><th class="text-end">Total</th><th class="pe-4">Status</th></tr></thead>
      <tbody>
      <?php foreach ($sales as $s): ?>
        <?php $lunas = $s['paid'] >= $s['total']; ?>
        <tr style="cursor:pointer" onclick="location.href='/sales/<?= $s['id'] ?>'">
          <td class="ps-4"><a class="num fw-semibold text-decoration-none" href="/sales/<?= $s['id'] ?>"><?= e($s['invoice_no']) ?></a><div class="small text-secondary"><?= (int) $s['item_count'] ?> item</div></td>
          <td class="text-secondary"><?= e(tgl($s['sale_date'], true)) ?></td>
          <td><?= e($s['customer_name'] ?? 'Umum') ?></td>
          <td><?= e(['cash' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS', 'credit' => 'Kredit'][$s['payment_method']] ?? $s['payment_method']) ?></td>
          <td class="text-end num fw-semibold"><?= money($s['total']) ?></td>
          <td class="pe-4"><span class="badge bg-<?= $lunas ? 'success' : 'warning' ?>-subtle text-<?= $lunas ? 'success' : 'warning' ?>-emphasis"><?= $lunas ? 'Lunas' : 'Belum lunas' ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$sales): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-receipt"></i>Tidak ada penjualan di periode ini.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>