<?php /** Dipakai untuk Piutang ($type='receivables') dan Hutang ($type='payables'). */ ?>
<div class="page-head">
  <div><h1><?= e($label) ?></h1><p><?= $type === 'receivables' ? 'Uang yang masih harus dibayar pelanggan.' : 'Uang yang masih harus kamu bayar ke supplier.' ?></p></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-4"><div class="card stat-card"><div class="stat-label">Total sisa <?= strtolower(e($label)) ?></div><div class="stat-value num"><?= money($openSum) ?></div></div></div>
  <div class="col-sm-6 col-xl-4"><div class="card stat-card"><div class="stat-label">Belum lunas</div><div class="stat-value num"><?= $openCount ?> <span class="fs-6 text-secondary">catatan</span></div></div></div>
  <div class="col-sm-6 col-xl-4"><div class="card stat-card"><div class="stat-label">Lewat jatuh tempo</div><div class="stat-value num <?= $overdue ? 'text-danger' : '' ?>"><?= $overdue ?> <span class="fs-6 text-secondary">catatan</span></div></div></div>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <div class="segmented">
      <?php foreach (['open' => 'Belum lunas', 'paid' => 'Lunas', 'all' => 'Semua'] as $k => $v): ?>
        <a href="/<?= $type ?>?status=<?= $k ?>" class="text-decoration-none"><button type="button" class="<?= $status === $k ? 'active' : '' ?>"><?= $v ?></button></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th class="ps-4"><?= e($partyLabel) ?></th><th>Referensi</th><th>Jatuh tempo</th><th class="text-end">Total</th><th class="text-end">Sisa</th><th class="text-end pe-4">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $sisa = $r['amount'] - $r['paid'];
          $late = $r['status'] === 'open' && $r['due_date'] && $r['due_date'] < date('Y-m-d');
          $pct  = $r['amount'] > 0 ? round($r['paid'] / $r['amount'] * 100) : 0;
        ?>
        <tr>
          <td class="ps-4 fw-semibold"><?= e($r['party_name'] ?? 'Umum') ?></td>
          <td><span class="num"><?= e($r['ref_no']) ?></span><div class="small text-secondary"><?= e(tgl($r['ref_date'])) ?></div></td>
          <td><?php if ($r['due_date']): ?><span class="<?= $late ? 'text-danger fw-semibold' : '' ?>"><?= e(tgl($r['due_date'])) ?></span><?= $late ? ' <span class="badge bg-danger-subtle text-danger-emphasis">Terlambat</span>' : '' ?><?php else: ?><span class="text-secondary">-</span><?php endif; ?></td>
          <td class="text-end num"><?= money($r['amount']) ?><div class="stock-bar mt-1 ms-auto" style="width:80px"><span style="width:<?= $pct ?>%"></span></div></td>
          <td class="text-end num fw-semibold <?= $sisa > 0 ? '' : 'text-secondary' ?>"><?= money($sisa) ?></td>
          <td class="text-end pe-4">
            <?php if ($r['status'] === 'open'): ?>
              <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payModal"
                      data-action="/<?= $type ?>/<?= $r['id'] ?>/pay" data-fill="<?= e(json_encode(['amount' => $sisa])) ?>">Catat bayar</button>
            <?php else: ?><span class="badge bg-success-subtle text-success-emphasis">Lunas</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-check2-circle"></i>Tidak ada catatan di sini.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <form class="modal-content" method="POST">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title fs-6 fw-semibold">Catat pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Nominal (Rp)</label>
        <input name="amount" type="number" min="1" step="any" data-max-from="amount" class="form-control num" required>
        <div class="form-text">Terisi otomatis dengan sisa. Ubah untuk mencicil.</div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary w-100">Simpan pembayaran</button></div>
    </form>
  </div>
</div>