<?php
$lunas = $sale['paid'] >= $sale['total'];
$methods = ['cash' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS', 'credit' => 'Kredit'];
$waText = "Halo " . ($sale['customer_name'] ?? '') . ", berikut faktur $business\nNo: {$sale['invoice_no']}\nTotal: " . money($sale['total']) . "\nStatus: " . ($lunas ? 'Lunas' : 'Sisa ' . money($sale['total'] - $sale['paid'])) . "\nTerima kasih!";
?>
<div class="page-head no-print">
  <div><a href="/sales" class="text-decoration-none text-secondary small"><i class="bi bi-arrow-left me-1"></i>Kembali</a></div>
  <div class="d-flex gap-2">
    <?php if (wa_link($sale['customer_phone'])): ?>
      <a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e(wa_link($sale['customer_phone'], $waText)) ?>"><i class="bi bi-whatsapp text-success me-1"></i>Kirim WhatsApp</a>
    <?php endif; ?>
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
  </div>
</div>

<div class="card invoice">
  <div class="card-body p-4 p-md-5">
    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
      <div>
        <div class="h5 fw-semibold mb-0"><?= e($business) ?></div>
        <div class="small text-secondary">Faktur penjualan</div>
      </div>
      <div class="text-md-end">
        <div class="num fw-semibold"><?= e($sale['invoice_no']) ?></div>
        <div class="small text-secondary"><?= e(tgl($sale['sale_date'], true)) ?></div>
        <span class="badge mt-1 bg-<?= $lunas ? 'success' : 'warning' ?>-subtle text-<?= $lunas ? 'success' : 'warning' ?>-emphasis"><?= $lunas ? 'Lunas' : 'Belum lunas' ?></span>
      </div>
    </div>

    <div class="small text-secondary">Pelanggan</div>
    <div class="fw-semibold mb-4"><?= e($sale['customer_name'] ?? 'Umum') ?></div>

    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Produk</th><th class="text-end">Harga</th><th class="text-end">Qty</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['product_name']) ?></td><td class="text-end num"><?= money($it['price']) ?></td><td class="text-end num"><?= $it['qty'] ?></td><td class="text-end num"><?= money($it['subtotal']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="ms-auto mt-3" style="max-width:300px">
      <div class="d-flex justify-content-between py-1"><span class="text-secondary">Subtotal</span><span class="num"><?= money($sale['subtotal']) ?></span></div>
      <?php if ($sale['discount'] > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-secondary">Diskon</span><span class="num">- <?= money($sale['discount']) ?></span></div><?php endif; ?>
      <div class="d-flex justify-content-between py-2 border-top fw-semibold fs-5"><span>Total</span><span class="num"><?= money($sale['total']) ?></span></div>
      <div class="d-flex justify-content-between py-1"><span class="text-secondary">Dibayar (<?= e($methods[$sale['payment_method']] ?? '') ?>)</span><span class="num"><?= money($sale['paid']) ?></span></div>
      <?php if (!$lunas): ?><div class="d-flex justify-content-between py-1 text-danger fw-semibold"><span>Sisa</span><span class="num"><?= money($sale['total'] - $sale['paid']) ?></span></div><?php endif; ?>
    </div>

    <p class="text-center text-secondary small mt-5 mb-0">Terima kasih sudah berbelanja.</p>
  </div>
</div>