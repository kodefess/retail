<?php /** Dipakai untuk Pelanggan ($type='customers') dan Supplier ($type='suppliers'). */ ?>
<div class="page-head">
  <div><h1><?= e($label) ?></h1><p>Daftar <?= strtolower(e($label)) ?> dan sisa <?= strtolower(e($debtLabel)) ?>-nya.</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#partyModal" data-action="/<?= $type ?>" data-title="Tambah <?= e($label) ?>" data-fill="{}">
    <i class="bi bi-plus-lg me-1"></i>Tambah
  </button>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <form class="input-icon"><i class="bi bi-search"></i>
      <input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Cari nama atau telepon">
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th class="ps-4">Nama</th><th>Telepon</th><th>Alamat</th><th class="text-end"><?= e($debtLabel) ?></th><th class="text-end pe-4">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr>
          <td class="ps-4">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar"><?= e(initials($i['name'])) ?></div>
              <span class="fw-semibold"><?= e($i['name']) ?></span>
            </div>
          </td>
          <td class="num">
            <?php if ($i['phone']): ?>
              <?= e($i['phone']) ?>
              <a href="<?= e(wa_link($i['phone'])) ?>" target="_blank" rel="noopener" class="text-success ms-1" title="Chat WhatsApp"><i class="bi bi-whatsapp"></i></a>
            <?php else: ?>-<?php endif; ?>
          </td>
          <td class="text-secondary"><?= e($i['address'] ?: '-') ?></td>
          <td class="text-end num"><?= $i['debt'] > 0 ? '<span class="text-danger fw-semibold">' . money($i['debt']) . '</span>' : '<span class="text-secondary">-</span>' ?></td>
          <td class="text-end pe-4 text-nowrap">
            <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#partyModal"
                    data-action="/<?= $type ?>/<?= $i['id'] ?>/update" data-title="Edit <?= e($label) ?>"
                    data-fill="<?= e(json_encode(['name' => $i['name'], 'phone' => $i['phone'], 'address' => $i['address']])) ?>" aria-label="Edit"><i class="bi bi-pencil"></i></button>
            <form class="d-inline" method="POST" action="/<?= $type ?>/<?= $i['id'] ?>/delete" data-confirm="Hapus data ini?">
              <?= csrf_field() ?>
              <button class="btn btn-soft btn-sm text-danger" aria-label="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-people"></i>Belum ada data.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Satu modal dipakai untuk tambah & edit. app.js mengisi form dari atribut data-* tombol -->
<div class="modal fade" id="partyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="/<?= $type ?>">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title fs-6 fw-semibold" data-modal-title>Tambah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Nama</label><input name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Telepon / WhatsApp</label><input name="phone" class="form-control num" placeholder="08xxxxxxxxxx"></div>
        <div><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div>
</div>