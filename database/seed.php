<?php
/**
 * SEEDER: mengisi data contoh supaya dashboard & chart langsung terlihat hidup.
 * Jalankan dari folder project:  php database/seed.php
 * Akun demo: demo@retail.test / password123
 */
require __DIR__ . '/../bootstrap/bootstrap.php';

$pdo = db();

if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) {
    exit("Data produk sudah ada, seeder dibatalkan agar tidak dobel.\n");
}

// Akun demo
$pdo->prepare('INSERT IGNORE INTO users (name, business_name, email, password) VALUES (?,?,?,?)')
    ->execute(['Sari Wulandari', 'Warung Bu Sari', 'demo@retail.test', password_hash('password123', PASSWORD_DEFAULT)]);

// Produk: [sku, nama, kategori, satuan, beli, jual, stok, min]
$products = [
    ['KPI-001', 'Kopi Susu Gula Aren', 'Minuman', 'cup', 9000, 18000, 120, 20],
    ['KPI-002', 'Es Teh Manis', 'Minuman', 'cup', 2500, 6000, 150, 30],
    ['MKN-001', 'Nasi Goreng Spesial', 'Makanan', 'porsi', 12000, 25000, 60, 15],
    ['MKN-002', 'Mie Ayam Bakso', 'Makanan', 'porsi', 9000, 20000, 70, 15],
    ['MKN-003', 'Ayam Geprek', 'Makanan', 'porsi', 11000, 22000, 55, 15],
    ['SNK-001', 'Pisang Goreng', 'Camilan', 'porsi', 4000, 10000, 80, 20],
    ['SNK-002', 'Keripik Singkong', 'Camilan', 'bungkus', 6000, 12000, 90, 20],
    ['SMB-001', 'Beras 5 kg', 'Sembako', 'karung', 62000, 72000, 25, 8],
    ['SMB-002', 'Minyak Goreng 1 L', 'Sembako', 'botol', 15500, 18500, 40, 12],
    ['SMB-003', 'Gula Pasir 1 kg', 'Sembako', 'kg', 14000, 17000, 35, 10],
    ['SMB-004', 'Telur Ayam 1 kg', 'Sembako', 'kg', 26000, 30000, 9, 10],
    ['MNM-001', 'Air Mineral 600 ml', 'Minuman', 'botol', 2000, 4000, 7, 24],
];
$ins = $pdo->prepare('INSERT INTO products (sku,name,category,unit,buy_price,sell_price,stock,min_stock) VALUES (?,?,?,?,?,?,?,?)');
foreach ($products as $p) $ins->execute($p);

foreach ([['Pak Budi', '081234567890', 'Jl. Melati 12'], ['Bu Ani', '085712345678', 'Jl. Kenanga 3'], ['Kantor Kelurahan', '0281555123', 'Jl. Raya 1'], ['Mas Dimas', '082198765432', 'Perum Griya Asri']] as $c) {
    $pdo->prepare('INSERT INTO customers (name, phone, address) VALUES (?,?,?)')->execute($c);
}
foreach ([['CV Sumber Pangan', '0281777888', 'Pasar Induk'], ['Toko Grosir Makmur', '081311223344', 'Jl. Pasar Baru'], ['Agen Gas & Galon', '081255566677', 'Jl. Industri']] as $s) {
    $pdo->prepare('INSERT INTO suppliers (name, phone, address) VALUES (?,?,?)')->execute($s);
}

$rows = $pdo->query('SELECT * FROM products')->fetchAll();

// Penjualan 45 hari terakhir, 2-7 transaksi per hari (lebih ramai di akhir pekan)
$saleIns  = $pdo->prepare('INSERT INTO sales (invoice_no, customer_id, subtotal, discount, total, paid, payment_method, sale_date) VALUES (?,?,?,?,?,?,?,?)');
$itemIns  = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, qty, price, cost, subtotal) VALUES (?,?,?,?,?,?,?)');
$cashIns  = $pdo->prepare("INSERT INTO cash_transactions (type, category, description, amount, transaction_date, ref_type, ref_id) VALUES ('income','Penjualan',?,?,?,'sale',?)");
$recvIns  = $pdo->prepare('INSERT INTO receivables (sale_id, customer_id, amount, paid, due_date) VALUES (?,?,?,?,?)');
$seq = 0;

for ($d = 45; $d >= 0; $d--) {
    $day   = strtotime("-$d days");
    $count = random_int(2, 5) + (in_array(date('w', $day), [0, 6]) ? 2 : 0);
    for ($i = 0; $i < $count; $i++) {
        $time = date('Y-m-d', $day) . ' ' . sprintf('%02d:%02d:00', random_int(8, 20), random_int(0, 59));
        if (strtotime($time) > time()) continue;

        $picked = array_rand($rows, random_int(1, 3));
        $picked = (array) $picked;
        $sub = 0; $lines = [];
        foreach ($picked as $idx) {
            $p = $rows[$idx]; $q = random_int(1, 4);
            $lines[] = [$p, $q]; $sub += $q * $p['sell_price'];
        }
        $credit = random_int(1, 12) === 1;
        $cust   = $credit ? random_int(1, 4) : (random_int(1, 4) === 1 ? random_int(1, 4) : null);
        $paid   = $credit ? round($sub * 0.3) : $sub;
        $method = $credit ? 'credit' : ['cash', 'cash', 'qris', 'transfer'][random_int(0, 3)];

        $saleIns->execute(['INV' . date('ymd', $day) . '-' . str_pad((string) (++$seq), 4, '0', STR_PAD_LEFT), $cust, $sub, 0, $sub, $paid, $method, $time]);
        $saleId = (int) $pdo->lastInsertId();
        foreach ($lines as [$p, $q]) $itemIns->execute([$saleId, $p['id'], $p['name'], $q, $p['sell_price'], $p['buy_price'], $q * $p['sell_price']]);
        if ($paid > 0) $cashIns->execute(['Penjualan #' . $saleId, $paid, $time, $saleId]);
        if ($credit)   $recvIns->execute([$saleId, $cust, $sub, $paid, date('Y-m-d', strtotime('+7 days'))]);
    }
}

// Pengeluaran operasional contoh
$exp = $pdo->prepare("INSERT INTO cash_transactions (type, category, description, amount, transaction_date) VALUES ('expense',?,?,?,?)");
foreach ([[ 'Listrik & air', 'Tagihan bulanan', 450000, 28], ['Sewa', 'Sewa kios', 1500000, 20], ['Transportasi', 'Ongkos belanja pasar', 85000, 9], ['Gaji', 'Gaji karyawan', 1200000, 5]] as [$c, $dsc, $amt, $ago]) {
    $exp->execute([$c, $dsc, $amt, date('Y-m-d 10:00:00', strtotime("-$ago days"))]);
}
$pdo->prepare("INSERT INTO cash_transactions (type, category, description, amount, transaction_date) VALUES ('income','Modal','Modal awal usaha',5000000,?)")
    ->execute([date('Y-m-d 08:00:00', strtotime('-45 days'))]);

echo "Selesai. Login dengan demo@retail.test / password123\n";