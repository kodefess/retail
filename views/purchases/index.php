<div class="page-head">
  <div><h1>Pembelian</h1><p><?= count($purchases) ?> pembelian &middot; total <span class="num"><?= money($total) ?></span></p></div>
  <a href="/purchases/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Pembelian baru</a>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <form class="row g-2">
      <div class="col-6 col-md-auto"><input type="date" class="form-control num" name="from" value="<?= e($from) ?>"></div>
      <div class="col-6 col-md-auto"><input type="date" class="form-control num" name="to" value="<?= e($to) ?>"></div>
      <div class="col-md-auto"><button class="btn btn-soft w-100">Terapkan</button></div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th class="ps-4">Referensi</th><th>Tanggal</th><th>Supplier</th><th class="text-end">Total</th><th class="pe-4">Status</th></tr></thead>
      <tbody>
      <?php foreach ($purchases as $p): ?>
        <?php $lunas = $p['paid'] >= $p['total']; ?>
        <tr>
          <td class="ps-4"><span class="num fw-semibold"><?= e($p['ref_no']) ?></span><div class="small text-secondary"><?= (int) $p['item_count'] ?> item</div></td>
          <td class="text-secondary"><?= e(tgl($p['purchase_date'], true)) ?></td>
          <td><?= e($p['supplier_name'] ?? '-') ?></td>
          <td class="text-end num fw-semibold"><?= money($p['total']) ?></td>
          <td class="pe-4"><span class="badge bg-<?= $lunas ? 'success' : 'warning' ?>-subtle text-<?= $lunas ? 'success' : 'warning' ?>-emphasis"><?= $lunas ? 'Lunas' : 'Hutang' ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$purchases): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-bag"></i>Belum ada pembelian di periode ini.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>