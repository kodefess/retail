<?php
namespace App\Controllers;

use App\Auth;

/** Halaman ringkasan + endpoint JSON untuk chart. */
class DashboardController
{
    public function index(): void
    {
        $db = db();

        $todaySales     = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
        $yesterdaySales = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(sale_date) = CURDATE() - INTERVAL 1 DAY")->fetchColumn();

        // Laba bulan ini = total penjualan - modal barang yang terjual
        $monthSales = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn();
        $monthCogs  = (float) $db->query("SELECT COALESCE(SUM(si.cost * si.qty),0) FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE DATE_FORMAT(s.sale_date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn();

        $stats = [
            'today'       => $todaySales,
            'trend'       => $yesterdaySales > 0 ? round(($todaySales - $yesterdaySales) / $yesterdaySales * 100) : null,
            'profit'      => $monthSales - $monthCogs,
            'cash'        => (float) $db->query("SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE -amount END),0) FROM cash_transactions")->fetchColumn(),
            'receivables' => (float) $db->query("SELECT COALESCE(SUM(amount-paid),0) FROM receivables WHERE status='open'")->fetchColumn(),
            'payables'    => (float) $db->query("SELECT COALESCE(SUM(amount-paid),0) FROM payables WHERE status='open'")->fetchColumn(),
            'stock_value' => (float) $db->query("SELECT COALESCE(SUM(stock*buy_price),0) FROM products")->fetchColumn(),
            'products'    => (int) $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        ];

        $lowStock    = $db->query('SELECT * FROM products WHERE stock <= min_stock ORDER BY stock ASC LIMIT 6')->fetchAll();
        $recentSales = $db->query('SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC LIMIT 6')->fetchAll();

        $hour     = (int) date('G');
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));

        view('dashboard/index', [
            'title'       => 'Dashboard',
            'active'      => 'dashboard',
            'greeting'    => $greeting,
            'stats'       => $stats,
            'lowStock'    => $lowStock,
            'recentSales' => $recentSales,
            'scripts'     => [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
                asset('js/dashboard.js'),
            ],
        ]);
    }

    /** GET /api/dashboard?range=7|30|90  -> JSON untuk 3 chart di dashboard */
    public function chart(): void
    {
        $range = (int) ($_GET['range'] ?? 7);
        $range = in_array($range, [7, 30, 90], true) ? $range : 7;
        $db    = db();
        $from  = date('Y-m-d', strtotime('-' . ($range - 1) . ' days'));

        // daftar tanggal lengkap supaya hari tanpa transaksi tetap muncul (bernilai 0)
        $days = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $days[] = date('Y-m-d', strtotime("-$i days"));
        }

        $sales = $this->perDay($db, "SELECT DATE(sale_date) d, SUM(total) v FROM sales WHERE DATE(sale_date) >= :f GROUP BY d", $from);
        $cogs  = $this->perDay($db, "SELECT DATE(s.sale_date) d, SUM(si.cost*si.qty) v FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE DATE(s.sale_date) >= :f GROUP BY d", $from);
        $in    = $this->perDay($db, "SELECT DATE(transaction_date) d, SUM(amount) v FROM cash_transactions WHERE type='income' AND DATE(transaction_date) >= :f GROUP BY d", $from);
        $out   = $this->perDay($db, "SELECT DATE(transaction_date) d, SUM(amount) v FROM cash_transactions WHERE type='expense' AND DATE(transaction_date) >= :f GROUP BY d", $from);

        $labels = $salesSeries = $profitSeries = $incomeSeries = $expenseSeries = [];
        foreach ($days as $d) {
            $labels[]        = date('d/m', strtotime($d));
            $salesSeries[]   = (float) ($sales[$d] ?? 0);
            $profitSeries[]  = (float) ($sales[$d] ?? 0) - (float) ($cogs[$d] ?? 0);
            $incomeSeries[]  = (float) ($in[$d] ?? 0);
            $expenseSeries[] = (float) ($out[$d] ?? 0);
        }

        $top = $db->prepare("SELECT si.product_name name, SUM(si.subtotal) revenue FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE DATE(s.sale_date) >= :f GROUP BY si.product_name ORDER BY revenue DESC LIMIT 5");
        $top->execute(['f' => $from]);

        json_response([
            'labels'  => $labels,
            'sales'   => $salesSeries,
            'profit'  => $profitSeries,
            'income'  => $incomeSeries,
            'expense' => $expenseSeries,
            'top'     => $top->fetchAll(),
            'totals'  => ['sales' => array_sum($salesSeries), 'profit' => array_sum($profitSeries)],
        ]);
    }

    /** Jalankan query yang mengembalikan kolom d (tanggal) & v (nilai) menjadi array ['2026-10-01' => 1000]. */
    private function perDay(\PDO $db, string $sql, string $from): array
    {
        $stmt = $db->prepare($sql);
        $stmt->execute(['f' => $from]);
        return array_column($stmt->fetchAll(), 'v', 'd');
    }
}