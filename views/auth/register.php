<h1 class="h3 fw-semibold mb-1" style="letter-spacing:-.02em">Buat akun baru</h1>
<p class="text-secondary mb-4">Butuh kurang dari satu menit.</p>

<?php if ($errors): ?>
  <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3"><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" action="/register" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="name">Nama lengkap</label>
    <input id="name" name="name" class="form-control" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" required autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label" for="business_name">Nama usaha</label>
    <input id="business_name" name="business_name" class="form-control" value="<?= e($old['business_name'] ?? '') ?>" placeholder="Contoh: Warung Bu Sari" required>
  </div>
  <div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input id="email" type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-sm-6">
      <label class="form-label" for="password">Kata sandi</label>
      <input id="password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
      <div class="form-text">Minimal 8 karakter.</div>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="password_confirmation">Ulangi kata sandi</label>
      <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
    </div>
  </div>
  <button class="btn btn-primary w-100 py-2">Buat akun</button>
</form>

<p class="text-center text-secondary small mt-4 mb-0">Sudah punya akun? <a href="/login" class="fw-semibold text-decoration-none">Masuk</a></p>