<?php
namespace App\Controllers;

/** Laporan per periode + ekspor CSV. */
class ReportController
{
    public function index(): void
    {
        [$from, $to] = $this->period();
        $db = db();
        $p  = ['from' => $from, 'to' => $to];

        $one = function (string $sql) use ($db, $p) {
            $s = $db->prepare($sql);
            $s->execute($p);
            return (float) $s->fetchColumn();
        };

        $sales     = $one('SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(sale_date) BETWEEN :from AND :to');
        $cogs      = $one('SELECT COALESCE(SUM(si.cost*si.qty),0) FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE DATE(s.sale_date) BETWEEN :from AND :to');
        $purchases = $one('SELECT COALESCE(SUM(total),0) FROM purchases WHERE DATE(purchase_date) BETWEEN :from AND :to');
        $income    = $one("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='income' AND DATE(transaction_date) BETWEEN :from AND :to");
        $expense   = $one("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='expense' AND DATE(transaction_date) BETWEEN :from AND :to");
        $count     = (int) $one('SELECT COUNT(*) FROM sales WHERE DATE(sale_date) BETWEEN :from AND :to');

        $top = $db->prepare('SELECT si.product_name AS name, SUM(si.qty) AS qty, SUM(si.subtotal) AS revenue, SUM(si.subtotal - si.cost*si.qty) AS profit
                             FROM sale_items si JOIN sales s ON s.id = si.sale_id
                             WHERE DATE(s.sale_date) BETWEEN :from AND :to
                             GROUP BY si.product_name ORDER BY revenue DESC LIMIT 10');
        $top->execute($p);

        $daily = $db->prepare('SELECT DATE(sale_date) d, SUM(total) v FROM sales WHERE DATE(sale_date) BETWEEN :from AND :to GROUP BY d ORDER BY d');
        $daily->execute($p);

        view('reports/index', [
            'title' => 'Laporan', 'active' => 'reports',
            'from' => $from, 'to' => $to,
            'summary' => [
                'sales' => $sales, 'profit' => $sales - $cogs, 'purchases' => $purchases,
                'income' => $income, 'expense' => $expense, 'net' => $income - $expense,
                'count' => $count, 'avg' => $count ? $sales / $count : 0,
            ],
            'topProducts' => $top->fetchAll(),
            'daily'       => $daily->fetchAll(),
            'scripts'     => ['https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js', asset('js/report.js')],
        ]);
    }

    /** Unduh CSV penjualan (bisa dibuka di Excel / Google Sheets). */
    public function export(): void
    {
        [$from, $to] = $this->period();
        $stmt = db()->prepare("SELECT s.invoice_no, s.sale_date, COALESCE(c.name,'Umum') AS customer, s.payment_method, s.subtotal, s.discount, s.total, s.paid
                               FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
                               WHERE DATE(s.sale_date) BETWEEN :from AND :to ORDER BY s.sale_date");
        $stmt->execute(['from' => $from, 'to' => $to]);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=penjualan_{$from}_{$to}.csv");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
        fputcsv($out, ['Invoice', 'Tanggal', 'Pelanggan', 'Metode', 'Subtotal', 'Diskon', 'Total', 'Dibayar']);
        foreach ($stmt->fetchAll() as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }

    /** Unduh laporan lengkap sebagai file Excel (.xlsx) yang sudah dirapikan. */
    public function exportExcel(): void
    {
        [$from, $to] = $this->period();
        (new \App\Services\ExcelReport($from, $to, \App\Auth::user()['business']))->download();
    }

    private function period(): array
    {
        $from = input('from', date('Y-m-01'));
        $to   = input('to', date('Y-m-d'));
        // validasi format tanggal supaya aman dipakai di query & nama file
        $ok = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        return [$ok($from) ? $from : date('Y-m-01'), $ok($to) ? $to : date('Y-m-d')];
    }
}