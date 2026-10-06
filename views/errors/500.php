<div class="empty py-5">
  <div class="display-4 fw-semibold num">500</div>
  <h1 class="h5 mt-2">Terjadi kesalahan di server</h1>
  <p>Coba muat ulang halaman. Jika masih terjadi, periksa koneksi database di file <code>.env</code>.</p>
  <?php if (!empty($message)): ?><pre class="text-start small bg-body-secondary rounded p-3 d-inline-block"><?= e($message) ?></pre><?php endif; ?>
  <div><a href="/" class="btn btn-primary">Kembali</a></div>
</div>