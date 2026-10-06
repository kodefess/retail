<?php
/**
 * DAFTAR ROUTE: peta URL -> controller.
 * Tambah fitur baru? Daftarkan URL-nya di sini.
 */
use App\Router;
use App\Controllers\{
    AuthController, DashboardController, ProductController, PartyController,
    SaleController, PurchaseController, DebtController, CashController, ReportController
};

$r = new Router();

$auth      = new AuthController();
$dashboard = new DashboardController();
$products  = new ProductController();
$customers = new PartyController('customers');
$suppliers = new PartyController('suppliers');
$sales     = new SaleController();
$purchases = new PurchaseController();
$recv      = new DebtController('receivables');
$pay       = new DebtController('payables');
$cash      = new CashController();
$reports   = new ReportController();

// --- Auth (public = boleh tanpa login) ---
$r->get('/login',     [$auth, 'showLogin'],    true);
$r->post('/login',    [$auth, 'login'],        true);
$r->get('/register',  [$auth, 'showRegister'], true);
$r->post('/register', [$auth, 'register'],     true);
$r->post('/logout',   [$auth, 'logout']);

// --- Dashboard + data JSON untuk chart ---
$r->get('/', [$dashboard, 'index']);
$r->get('/api/dashboard', [$dashboard, 'chart']);

// --- Produk ---
$r->get('/products',               [$products, 'index']);
$r->get('/products/create',        [$products, 'create']);
$r->post('/products',              [$products, 'store']);
$r->get('/products/{id}/edit',     [$products, 'edit']);
$r->post('/products/{id}/update',  [$products, 'update']);
$r->post('/products/{id}/delete',  [$products, 'destroy']);

// --- Pelanggan & Supplier (satu controller, dua tabel) ---
foreach (['customers' => $customers, 'suppliers' => $suppliers] as $base => $ctrl) {
    $r->get("/$base",                [$ctrl, 'index']);
    $r->post("/$base",               [$ctrl, 'store']);
    $r->post("/$base/{id}/update",   [$ctrl, 'update']);
    $r->post("/$base/{id}/delete",   [$ctrl, 'destroy']);
}

// --- Penjualan (kasir/POS) ---
$r->get('/sales',         [$sales, 'index']);
$r->get('/sales/create',  [$sales, 'create']);
$r->post('/sales',        [$sales, 'store']);
$r->get('/sales/{id}',    [$sales, 'show']);

// --- Pembelian stok ---
$r->get('/purchases',        [$purchases, 'index']);
$r->get('/purchases/create', [$purchases, 'create']);
$r->post('/purchases',       [$purchases, 'store']);

// --- Piutang & Hutang ---
$r->get('/receivables',            [$recv, 'index']);
$r->post('/receivables/{id}/pay',  [$recv, 'pay']);
$r->get('/payables',               [$pay, 'index']);
$r->post('/payables/{id}/pay',     [$pay, 'pay']);

// --- Kas ---
$r->get('/cash',                [$cash, 'index']);
$r->post('/cash',               [$cash, 'store']);
$r->post('/cash/{id}/delete',   [$cash, 'destroy']);

// --- Laporan ---
$r->get('/reports',        [$reports, 'index']);
$r->get('/reports/export', [$reports, 'export']);

return $r;