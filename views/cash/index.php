<div class="page-head">
  <div><h1>Kas</h1><p>Pemasukan dan pengeluaran. Transaksi penjualan & pembelian tercatat otomatis.</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cashModal" data-fill="{}"><i class="bi bi-plus-lg me-1"></i>Catat transaksi</button>
</div>

<div class="row g-3 mb-3">
  <?php foreach ([['Saldo kas (semua waktu)', $balance, 'bi-wallet2', 'violet'], ['Pemasukan periode ini', $income, 'bi-arrow-down-left', ''], ['Pengeluaran periode ini', $expense, 'bi-arrow-up-right', 'danger']] as [$l, $v, $i, $t]): ?>
    <div class="col-md-4">
      <div class="card stat-card d-flex flex-row justify-content-between align-items-start">
        <div><div class="stat-label"><?= $l ?></div><div class="stat-value num"><?= money($v) ?></div></div>
        <div class="stat-icon <?= $t ?>"><i class="bi <?= $i ?>"></i></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <form class="row g-2">
      <div class="col-6 col-md-auto"><input type="date" class="form-control num" name="from" value="<?= e($from) ?>"></div>
      <div class="col-6 col-md-auto"><input type="date" class="form-control num" name="to" value="<?= e($to) ?>"></div>
      <div class="col-md-auto">
        <select name="type" class="form-select">
          <option value="">Semua jenis</option>
          <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Pemasukan</option>
          <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
        </select>
      </div>
      <div class="col-md-auto"><button class="btn btn-soft w-100">Terapkan</button></div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th class="ps-4">Tanggal</th><th>Jenis</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Nominal</th><th class="pe-4"></th></tr></thead>
      <tbody>
      <?php foreach ($transactions as $t): ?>
        <?php $in = $t['type'] === 'income'; ?>
        <tr>
          <td class="ps-4 text-secondary text-nowrap"><?= e(tgl($t['transaction_date'], true)) ?></td>
          <td><span class="badge bg-<?= $in ? 'success' : 'danger' ?>-subtle text-<?= $in ? 'success' : 'danger' ?>-emphasis"><?= $in ? 'Masuk' : 'Keluar' ?></span></td>
          <td><?= e($t['category']) ?></td>
          <td class="text-secondary"><?= e($t['description'] ?: '-') ?></td>
          <td class="text-end num fw-semibold <?= $in ? 'text-success' : 'text-danger' ?>"><?= $in ? '+' : '-' ?> <?= money($t['amount']) ?></td>
          <td class="pe-4 text-end">
            <?php if (!$t['ref_type']): ?>
              <form method="POST" action="/cash/<?= $t['id'] ?>/delete" data-confirm="Hapus transaksi ini?">
                <?= csrf_field() ?><button class="btn btn-soft btn-sm text-danger" aria-label="Hapus"><i class="bi bi-trash"></i></button>
              </form>
            <?php else: ?><i class="bi bi-lock text-secondary" title="Otomatis dari transaksi"></i><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$transactions): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-wallet2"></i>Belum ada transaksi di periode ini.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="cashModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="/cash">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title fs-6 fw-semibold">Catat kas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-6"><label class="form-label">Jenis</label>
            <select name="type" class="form-select"><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option></select></div>
          <div class="col-6"><label class="form-label">Kategori</label><input name="category" class="form-control" list="cats" placeholder="Contoh: Listrik" required>
            <datalist id="cats"><option>Modal</option><option>Gaji</option><option>Listrik & air</option><option>Sewa</option><option>Transportasi</option><option>Lainnya</option></datalist></div>
          <div class="col-6"><label class="form-label">Nominal (Rp)</label><input name="amount" type="number" min="1" step="any" class="form-control num" required></div>
          <div class="col-6"><label class="form-label">Tanggal</label><input name="transaction_date" type="datetime-local" class="form-control num" value="<?= date('Y-m-d\TH:i') ?>"></div>
          <div class="col-12"><label class="form-label">Keterangan</label><input name="description" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div>
</div>