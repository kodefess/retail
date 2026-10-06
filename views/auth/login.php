<h1 class="h3 fw-semibold mb-1" style="letter-spacing:-.02em">Masuk ke akun</h1>
<p class="text-secondary mb-4">Lanjutkan mengelola usahamu.</p>

<?php if ($errors): ?>
  <div class="alert alert-danger py-2 small"><?php foreach ($errors as $x): ?><div><?= e($x) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<form method="POST" action="/login" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <div class="input-icon">
      <i class="bi bi-envelope"></i>
      <input id="email" type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>" placeholder="nama@usaha.com" autocomplete="email" required autofocus>
    </div>
  </div>
  <div class="mb-4">
    <label class="form-label" for="password">Kata sandi</label>
    <div class="input-group">
      <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
      <button class="btn btn-soft" type="button" data-toggle-password="#password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
    </div>
  </div>
  <button class="btn btn-primary w-100 py-2">Masuk</button>
</form>

<p class="text-center text-secondary small mt-4 mb-0">Belum punya akun? <a href="/register" class="fw-semibold text-decoration-none">Daftar gratis</a></p>