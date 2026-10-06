<?php
/**
 * Dipakai oleh dua halaman: Kasir (mode=sale) dan Pembelian (mode=purchase).
 * Tampilan interaktifnya dikerjakan JavaScript di public/assets/js/pos.js
 */
$isSale = $mode === 'sale';
?>
<script>window.POS = <?= json_script(['mode' => $mode, 'products' => $products]) ?>;</script>

<div class="page-head">
  <div>
    <h1><?= $isSale ? 'Kasir' : 'Pembelian stok' ?></h1>
    <p><?= $isSale ? 'Pilih produk, atur pembayaran, lalu simpan.' : 'Catat barang masuk dari supplier. Stok dan harga beli ikut diperbarui.' ?></p>
  </div>
</div>

<div class="row g-3" id="pos">
  <!-- Kolom kiri: daftar produk -->
  <div class="col-lg-7 col-xl-8">
    <div class="card">
      <div class="card-body">
        <div class="input-icon mb-3">
          <i class="bi bi-search"></i>
          <input id="search" class="form-control" placeholder="Cari produk atau scan barcode  (tekan / untuk fokus)" autocomplete="off">
        </div>
        <div id="chips" class="chips mb-3"></div>
        <div id="productGrid" class="pos-products"></div>
      </div>
    </div>
  </div>

  <!-- Kolom kanan: keranjang -->
  <div class="col-lg-5 col-xl-4">
    <div class="card cart-card" id="cartCard">
      <form id="posForm" method="POST" action="<?= $isSale ? '/sales' : '/purchases' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="cart" id="cartInput">

        <div class="card-header d-flex justify-content-between align-items-center">
          <h2 class="card-title"><?= $isSale ? 'Keranjang' : 'Daftar belanja' ?></h2>
        </div>

        <div id="cartLines" class="cart-lines"></div>

        <div class="p-3 border-top">
          <div class="mb-3">
            <label class="form-label"><?= $isSale ? 'Pelanggan (opsional)' : 'Supplier' ?></label>
            <select name="party_id" class="form-select">
              <option value=""><?= $isSale ? 'Umum' : '- pilih -' ?></option>
              <?php foreach ($parties as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <?php if ($isSale): ?>
            <div class="mb-3">
              <label class="form-label">Diskon (Rp)</label>
              <input id="discount" name="discount" type="number" min="0" value="0" class="form-control num">
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Pembayaran</label>
            <select id="method" name="method" class="form-select">
              <option value="cash">Tunai</option>
              <option value="transfer">Transfer</option>
              <?php if ($isSale): ?><option value="qris">QRIS</option><?php endif; ?>
              <option value="credit"><?= $isSale ? 'Kredit (piutang)' : 'Hutang' ?></option>
            </select>
          </div>

          <div id="paidWrap" class="mb-3 d-none">
            <label class="form-label" id="paidLabel" for="paid">Dibayar</label>
            <input id="paid" name="paid" type="number" min="0" class="form-control num" placeholder="0">
            <div class="form-text" id="paidNote"></div>
          </div>
          <div id="dueWrap" class="mb-3 d-none">
            <label class="form-label">Jatuh tempo</label>
            <input type="date" name="due_date" class="form-control num">
          </div>

          <div class="d-flex justify-content-between small text-secondary"><span>Subtotal</span><span class="num" id="subtotal">Rp 0</span></div>
          <div class="d-flex justify-content-between fs-5 fw-semibold mt-1 mb-3"><span>Total</span><span class="num" id="total">Rp 0</span></div>

          <button id="submitBtn" class="btn btn-primary w-100 py-2" disabled><?= $isSale ? 'Simpan penjualan' : 'Simpan pembelian' ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Tombol melayang di HP: lompat ke keranjang -->
<button type="button" id="cartFab" class="btn btn-primary cart-fab rounded-pill px-3 align-items-center gap-2">
  <i class="bi bi-cart3"></i><span class="num" id="fabCount">0</span><span class="num" id="fabTotal">Rp 0</span>
</button>