<?php
namespace App\Controllers;

use App\Auth;

/** Penjualan: riwayat, kasir (POS), simpan transaksi, dan faktur. */
class SaleController
{
    public function index(): void
    {
        $from   = input('from', date('Y-m-01'));
        $to     = input('to', date('Y-m-d'));
        $search = input('search');

        $sql = 'SELECT s.*, c.name AS customer_name,
                       (SELECT COUNT(*) FROM sale_items i WHERE i.sale_id = s.id) AS item_count
                FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
                WHERE DATE(s.sale_date) BETWEEN :from AND :to';
        $params = ['from' => $from, 'to' => $to];
        if ($search !== '') {
            $sql .= ' AND (s.invoice_no LIKE :q1 OR c.name LIKE :q2)';
            $params['q1'] = "%$search%";
            $params['q2'] = "%$search%";
        }
        $sql .= ' ORDER BY s.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();

        view('sales/index', [
            'title'  => 'Penjualan',
            'active' => 'sales',
            'sales'  => $sales,
            'total'  => array_sum(array_column($sales, 'total')),
            'from'   => $from,
            'to'     => $to,
            'search' => $search,
        ]);
    }

    /** Halaman kasir. Produk dikirim ke JavaScript (pos.js) sebagai JSON. */
    public function create(): void
    {
        view('pos/form', [
            'title'     => 'Kasir',
            'active'    => 'pos',
            'mode'      => 'sale',
            'products'  => db()->query('SELECT id, sku, name, COALESCE(category,"Lainnya") AS category, unit, stock, sell_price AS price FROM products ORDER BY name')->fetchAll(),
            'parties'   => db()->query('SELECT id, name FROM customers ORDER BY name')->fetchAll(),
            'scripts'   => [asset('js/pos.js')],
        ]);
    }

    public function store(): void
    {
        verify_csrf();

        $cart     = json_decode((string) ($_POST['cart'] ?? '[]'), true);
        $method   = in_array(input('method'), ['cash', 'transfer', 'qris', 'credit'], true) ? input('method') : 'cash';
        $customer = (int) input('party_id') ?: null;
        $discount = max(0, (float) input('discount', '0'));

        if (!is_array($cart) || !$cart) {
            flash('danger', 'Keranjang masih kosong.');
            redirect('/sales/create');
        }
        if ($method === 'credit' && !$customer) {
            flash('danger', 'Pilih pelanggan untuk penjualan kredit (piutang).');
            redirect('/sales/create');
        }

        $pdo = db();
        try {
            $pdo->beginTransaction(); // semua langkah berhasil, atau semuanya dibatalkan

            $items    = [];
            $subtotal = 0;
            foreach ($cart as $line) {
                $qty   = (int) ($line['qty'] ?? 0);
                $price = max(0, (float) ($line['price'] ?? 0));
                if ($qty < 1) continue;

                // FOR UPDATE mengunci baris produk agar dua kasir tidak menjual stok yang sama
                $p = $pdo->prepare('SELECT * FROM products WHERE id = :id FOR UPDATE');
                $p->execute(['id' => (int) ($line['id'] ?? 0)]);
                $product = $p->fetch();

                if (!$product) throw new \RuntimeException('Ada produk yang sudah tidak tersedia.');
                if ($product['stock'] < $qty) {
                    throw new \RuntimeException('Stok "' . $product['name'] . '" tidak cukup (sisa ' . $product['stock'] . ').');
                }

                $items[]   = ['product' => $product, 'qty' => $qty, 'price' => $price];
                $subtotal += $qty * $price;
            }
            if (!$items) throw new \RuntimeException('Keranjang masih kosong.');

            $discount = min($discount, $subtotal);
            $total    = $subtotal - $discount;
            $paid     = $method === 'credit' ? min($total, max(0, (float) input('paid', '0'))) : $total;
            $now      = date('Y-m-d H:i:s');

            $count = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
            $invoice = 'INV' . date('ymd') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            $pdo->prepare('INSERT INTO sales (invoice_no, customer_id, subtotal, discount, total, paid, payment_method, sale_date) VALUES (:inv,:c,:sub,:dis,:tot,:paid,:m,:d)')
                ->execute(['inv' => $invoice, 'c' => $customer, 'sub' => $subtotal, 'dis' => $discount, 'tot' => $total, 'paid' => $paid, 'm' => $method, 'd' => $now]);
            $saleId = (int) $pdo->lastInsertId();

            $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, qty, price, cost, subtotal) VALUES (:s,:p,:n,:q,:pr,:c,:sub)');
            $cutStock = $pdo->prepare('UPDATE products SET stock = stock - :q WHERE id = :id');
            foreach ($items as $it) {
                $insItem->execute(['s' => $saleId, 'p' => $it['product']['id'], 'n' => $it['product']['name'], 'q' => $it['qty'], 'pr' => $it['price'], 'c' => $it['product']['buy_price'], 'sub' => $it['qty'] * $it['price']]);
                $cutStock->execute(['q' => $it['qty'], 'id' => $it['product']['id']]);
            }

            // uang yang benar-benar diterima otomatis masuk buku kas
            if ($paid > 0) {
                $pdo->prepare("INSERT INTO cash_transactions (type, category, description, amount, transaction_date, ref_type, ref_id) VALUES ('income','Penjualan',:d,:a,:t,'sale',:r)")
                    ->execute(['d' => 'Penjualan ' . $invoice, 'a' => $paid, 't' => $now, 'r' => $saleId]);
            }
            // sisa yang belum dibayar menjadi piutang
            if ($total - $paid > 0) {
                $due = input('due_date') ?: null;
                $pdo->prepare('INSERT INTO receivables (sale_id, customer_id, amount, paid, due_date) VALUES (:s,:c,:a,:p,:due)')
                    ->execute(['s' => $saleId, 'c' => $customer, 'a' => $total, 'p' => $paid, 'due' => $due]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', $e instanceof \RuntimeException ? $e->getMessage() : 'Transaksi gagal disimpan. Coba lagi.');
            redirect('/sales/create');
        }

        flash('success', 'Penjualan ' . $invoice . ' tersimpan.');
        redirect('/sales/' . $saleId);
    }

    /** Faktur / struk penjualan (bisa dicetak). */
    public function show(int $id): void
    {
        $stmt = db()->prepare('SELECT s.*, c.name AS customer_name, c.phone AS customer_phone FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = :id');
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();

        if (!$sale) {
            http_response_code(404);
            view('errors/404', ['title' => 'Faktur tidak ditemukan']);
            return;
        }

        $items = db()->prepare('SELECT * FROM sale_items WHERE sale_id = :id');
        $items->execute(['id' => $id]);

        view('sales/show', ['title' => $sale['invoice_no'], 'active' => 'sales', 'sale' => $sale, 'items' => $items->fetchAll(), 'business' => Auth::user()['business']]);
    }
}