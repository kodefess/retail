<div class="mb-3">
  <a href="/products" class="text-decoration-none text-secondary small"><i class="bi bi-arrow-left me-1"></i>Kembali ke produk</a>
  <h1 class="h4 fw-semibold mt-2" style="letter-spacing:-.02em"><?= $mode === 'edit' ? 'Edit produk' : 'Tambah produk' ?></h1>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card" style="max-width:860px">
  <div class="card-body p-4">
    <form method="POST" action="<?= $mode === 'edit' ? '/products/' . (int) $product['id'] . '/update' : '/products' ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">SKU / kode</label><input name="sku" class="form-control num" value="<?= e($product['sku']) ?>" required></div>
        <div class="col-md-8"><label class="form-label">Nama produk</label><input name="name" class="form-control" value="<?= e($product['name']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Kategori</label><input name="category" class="form-control" value="<?= e($product['category']) ?>" placeholder="Contoh: Minuman"></div>
        <div class="col-md-4"><label class="form-label">Satuan</label><input name="unit" class="form-control" value="<?= e($product['unit']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Stok minimum</label><input type="number" name="min_stock" min="0" class="form-control num" value="<?= e($product['min_stock']) ?>"><div class="form-text">Peringatan muncul di bawah angka ini.</div></div>
        <div class="col-md-4"><label class="form-label">Harga beli (modal)</label><input type="number" id="buy" name="buy_price" min="0" step="any" class="form-control num" value="<?= e($product['buy_price']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Harga jual</label><input type="number" id="sell" name="sell_price" min="0" step="any" class="form-control num" value="<?= e($product['sell_price']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Margin</label><div id="margin" class="form-control num bg-body-secondary">-</div></div>
        <div class="col-md-4"><label class="form-label">Stok saat ini</label><input type="number" name="stock" min="0" class="form-control num" value="<?= e($product['stock']) ?>"></div>
      </div>
      <div class="text-end mt-4">
        <a href="/products" class="btn btn-soft me-2">Batal</a>
        <button class="btn btn-primary">Simpan produk</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Hitung margin otomatis saat harga diubah: (jual - beli) / jual
  (() => {
    const buy = document.getElementById('buy'), sell = document.getElementById('sell'), out = document.getElementById('margin');
    const calc = () => {
      const b = +buy.value, s = +sell.value;
      out.textContent = s > 0 ? Math.round((s - b) / s * 100) + '%  (Rp ' + new Intl.NumberFormat('id-ID').format(s - b) + ')' : '-';
    };
    buy.addEventListener('input', calc); sell.addEventListener('input', calc); calc();
  })();
</script>