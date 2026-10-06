<?php
namespace App\Controllers;

/** Pembelian stok dari supplier. Menambah stok & memperbarui harga beli produk. */
class PurchaseController
{
    public function index(): void
    {
        $from = input('from', date('Y-m-01'));
        $to   = input('to', date('Y-m-d'));

        $stmt = db()->prepare('SELECT p.*, s.name AS supplier_name,
                                      (SELECT COUNT(*) FROM purchase_items i WHERE i.purchase_id = p.id) AS item_count
                               FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id
                               WHERE DATE(p.purchase_date) BETWEEN :from AND :to ORDER BY p.id DESC');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $purchases = $stmt->fetchAll();

        view('purchases/index', [
            'title'     => 'Pembelian',
            'active'    => 'purchases',
            'purchases' => $purchases,
            'total'     => array_sum(array_column($purchases, 'total')),
            'from'      => $from,
            'to'        => $to,
        ]);
    }

    public function create(): void
    {
        view('pos/form', [
            'title'    => 'Pembelian Baru',
            'active'   => 'purchases',
            'mode'     => 'purchase',
            'products' => db()->query('SELECT id, sku, name, COALESCE(category,"Lainnya") AS category, unit, stock, buy_price AS price FROM products ORDER BY name')->fetchAll(),
            'parties'  => db()->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll(),
            'scripts'  => [asset('js/pos.js')],
        ]);
    }

    public function store(): void
    {
        verify_csrf();

        $cart     = json_decode((string) ($_POST['cart'] ?? '[]'), true);
        $method   = in_array(input('method'), ['cash', 'transfer', 'credit'], true) ? input('method') : 'cash';
        $supplier = (int) input('party_id') ?: null;

        if (!is_array($cart) || !$cart) {
            flash('danger', 'Daftar belanja masih kosong.');
            redirect('/purchases/create');
        }
        if ($method === 'credit' && !$supplier) {
            flash('danger', 'Pilih supplier untuk pembelian hutang.');
            redirect('/purchases/create');
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();

            $total = 0;
            $rows  = [];
            foreach ($cart as $line) {
                $qty   = (int) ($line['qty'] ?? 0);
                $price = max(0, (float) ($line['price'] ?? 0));
                if ($qty < 1) continue;

                $p = $pdo->prepare('SELECT id, name FROM products WHERE id = :id FOR UPDATE');
                $p->execute(['id' => (int) ($line['id'] ?? 0)]);
                $product = $p->fetch();
                if (!$product) throw new \RuntimeException('Ada produk yang tidak ditemukan.');

                $rows[] = ['product' => $product, 'qty' => $qty, 'price' => $price];
                $total += $qty * $price;
            }
            if (!$rows) throw new \RuntimeException('Daftar belanja masih kosong.');

            $paid = $method === 'credit' ? min($total, max(0, (float) input('paid', '0'))) : $total;
            $now  = date('Y-m-d H:i:s');
            $count = $pdo->query("SELECT COUNT(*) FROM purchases WHERE DATE(purchase_date) = CURDATE()")->fetchColumn();
            $ref   = 'PO' . date('ymd') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            $pdo->prepare('INSERT INTO purchases (ref_no, supplier_id, total, paid, purchase_date) VALUES (:r,:s,:t,:p,:d)')
                ->execute(['r' => $ref, 's' => $supplier, 't' => $total, 'p' => $paid, 'd' => $now]);
            $purchaseId = (int) $pdo->lastInsertId();

            $insItem = $pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, product_name, qty, price, subtotal) VALUES (:pu,:p,:n,:q,:pr,:s)');
            $addStock = $pdo->prepare('UPDATE products SET stock = stock + :q, buy_price = :price WHERE id = :id');
            foreach ($rows as $r) {
                $insItem->execute(['pu' => $purchaseId, 'p' => $r['product']['id'], 'n' => $r['product']['name'], 'q' => $r['qty'], 'pr' => $r['price'], 's' => $r['qty'] * $r['price']]);
                $addStock->execute(['q' => $r['qty'], 'price' => $r['price'], 'id' => $r['product']['id']]);
            }

            if ($paid > 0) {
                $pdo->prepare("INSERT INTO cash_transactions (type, category, description, amount, transaction_date, ref_type, ref_id) VALUES ('expense','Pembelian Stok',:d,:a,:t,'purchase',:r)")
                    ->execute(['d' => 'Pembelian ' . $ref, 'a' => $paid, 't' => $now, 'r' => $purchaseId]);
            }
            if ($total - $paid > 0) {
                $pdo->prepare('INSERT INTO payables (purchase_id, supplier_id, amount, paid, due_date) VALUES (:pu,:s,:a,:p,:due)')
                    ->execute(['pu' => $purchaseId, 's' => $supplier, 'a' => $total, 'p' => $paid, 'due' => input('due_date') ?: null]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', $e instanceof \RuntimeException ? $e->getMessage() : 'Pembelian gagal disimpan. Coba lagi.');
            redirect('/purchases/create');
        }

        flash('success', 'Pembelian ' . $ref . ' tersimpan, stok bertambah.');
        redirect('/purchases');
    }
}