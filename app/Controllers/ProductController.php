<?php
namespace App\Controllers;

/** CRUD produk & stok. */
class ProductController
{
    public function index(): void
    {
        $search = input('search');
        $filter = input('filter'); // 'low' = hanya stok menipis

        $sql    = 'SELECT * FROM products WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (name LIKE :q1 OR sku LIKE :q2 OR category LIKE :q3)';
            // PDO native prepare: satu nama placeholder tidak boleh dipakai dua kali
            $params = ['q1' => "%$search%", 'q2' => "%$search%", 'q3' => "%$search%"];
        }
        if ($filter === 'low') {
            $sql .= ' AND stock <= min_stock';
        }
        $sql .= ' ORDER BY name ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        view('products/index', [
            'title'    => 'Produk & Stok',
            'active'   => 'products',
            'products' => $stmt->fetchAll(),
            'search'   => $search,
            'filter'   => $filter,
            'lowCount' => low_stock_count(),
        ]);
    }

    public function create(): void
    {
        $this->form('create', $this->blank(), []);
    }

    public function store(): void
    {
        verify_csrf();
        $data   = $this->collect();
        $errors = $this->validate($data);

        if ($errors) {
            $this->form('create', $data, $errors);
            return;
        }

        $stmt = db()->prepare('INSERT INTO products (sku,name,category,unit,buy_price,sell_price,stock,min_stock) VALUES (:sku,:name,:category,:unit,:buy_price,:sell_price,:stock,:min_stock)');
        $stmt->execute($data);
        flash('success', 'Produk "' . $data['name'] . '" ditambahkan.');
        redirect('/products');
    }

    public function edit(int $id): void
    {
        $this->form('edit', $this->find($id), []);
    }

    public function update(int $id): void
    {
        verify_csrf();
        $this->find($id); // pastikan ada (404 kalau tidak)
        $data   = $this->collect();
        $errors = $this->validate($data, $id);

        if ($errors) {
            $this->form('edit', $data + ['id' => $id], $errors);
            return;
        }

        $stmt = db()->prepare('UPDATE products SET sku=:sku,name=:name,category=:category,unit=:unit,buy_price=:buy_price,sell_price=:sell_price,stock=:stock,min_stock=:min_stock WHERE id=:id');
        $stmt->execute($data + ['id' => $id]);
        flash('success', 'Produk diperbarui.');
        redirect('/products');
    }

    public function destroy(int $id): void
    {
        verify_csrf();
        db()->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Produk dihapus. Riwayat transaksi tetap tersimpan.');
        redirect('/products');
    }

    // ---------- helper privat ----------

    private function blank(): array
    {
        return ['sku' => '', 'name' => '', 'category' => '', 'unit' => 'pcs', 'buy_price' => 0, 'sell_price' => 0, 'stock' => 0, 'min_stock' => 5];
    }

    private function find(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            view('errors/404', ['title' => 'Produk tidak ditemukan']);
            exit;
        }
        return $row;
    }

    /** Ambil & bersihkan input dari form. */
    private function collect(): array
    {
        return [
            'sku'        => strtoupper(input('sku')),
            'name'       => input('name'),
            'category'   => input('category'),
            'unit'       => input('unit', 'pcs'),
            'buy_price'  => max(0, (float) input('buy_price', '0')),
            'sell_price' => max(0, (float) input('sell_price', '0')),
            'stock'      => max(0, (int) input('stock', '0')),
            'min_stock'  => max(0, (int) input('min_stock', '0')),
        ];
    }

    private function validate(array $d, ?int $ignoreId = null): array
    {
        $errors = [];
        if ($d['sku'] === '')  $errors[] = 'SKU wajib diisi.';
        if ($d['name'] === '') $errors[] = 'Nama produk wajib diisi.';
        if ($d['unit'] === '') $errors[] = 'Satuan wajib diisi.';

        if ($d['sku'] !== '') {
            $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku AND id <> :id');
            $stmt->execute(['sku' => $d['sku'], 'id' => $ignoreId ?? 0]);
            if ($stmt->fetchColumn() > 0) $errors[] = 'SKU "' . $d['sku'] . '" sudah dipakai produk lain.';
        }
        return $errors;
    }

    private function form(string $mode, array $product, array $errors): void
    {
        view('products/form', ['title' => $mode === 'edit' ? 'Edit Produk' : 'Tambah Produk', 'active' => 'products', 'mode' => $mode, 'product' => $product, 'errors' => $errors]);
    }
}