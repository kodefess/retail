<div class="page-head">
  <div><h1>Produk & stok</h1><p>Kelola barang dan persediaan.</p></div>
  <a href="/products/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah produk</a>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <form class="row g-2 align-items-center">
      <div class="col">
        <div class="input-icon">
          <i class="bi bi-search"></i>
          <input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Cari SKU, nama, atau kategori">
        </div>
      </div>
      <div class="col-auto">
        <div class="segmented">
          <button type="submit" name="filter" value="" class="<?= $filter === '' ? 'active' : '' ?>">Semua</button>
          <button type="submit" name="filter" value="low" class="<?= $filter === 'low' ? 'active' : '' ?>">Menipis<?= $lowCount ? " ($lowCount)" : '' ?></button>
        </div>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr><th class="ps-4">Produk</th><th>Kategori</th><th class="text-end">Harga beli</th><th class="text-end">Harga jual</th><th>Stok</th><th class="text-end pe-4">Aksi</th></tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): ?>
        <?php
          $low = $p['stock'] <= $p['min_stock'];
          $pct = $p['min_stock'] > 0 ? min(100, round($p['stock'] / ($p['min_stock'] * 3) * 100)) : 100;
        ?>
        <tr>
          <td class="ps-4"><div class="fw-semibold"><?= e($p['name']) ?></div><div class="small text-secondary num"><?= e($p['sku']) ?></div></td>
          <td><?= e($p['category'] ?: '-') ?></td>
          <td class="text-end num text-secondary"><?= money($p['buy_price']) ?></td>
          <td class="text-end num"><?= money($p['sell_price']) ?></td>
          <td style="min-width:150px">
            <div class="d-flex align-items-center gap-2">
              <span class="num"><?= num($p['stock']) ?> <span class="text-secondary"><?= e($p['unit']) ?></span></span>
              <span class="badge bg-<?= $low ? 'warning' : 'success' ?>-subtle text-<?= $low ? 'warning' : 'success' ?>-emphasis"><?= $low ? 'Menipis' : 'Aman' ?></span>
            </div>
            <div class="stock-bar <?= $low ? 'low' : '' ?> mt-1"><span style="width:<?= $pct ?>%"></span></div>
          </td>
          <td class="text-end pe-4 text-nowrap">
            <a class="btn btn-soft btn-sm" href="/products/<?= $p['id'] ?>/edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
            <form class="d-inline" method="POST" action="/products/<?= $p['id'] ?>/delete" data-confirm="Hapus produk ini?">
              <?= csrf_field() ?>
              <button class="btn btn-soft btn-sm text-danger" aria-label="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <tr><td colspan="6"><div class="empty"><i class="bi bi-box-seam"></i>Belum ada produk. <a href="/products/create">Tambah produk pertama</a>.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>