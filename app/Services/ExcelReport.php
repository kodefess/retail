<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Membuat file Excel (.xlsx) laporan yang sudah rapi dan siap pakai.
 * Isi workbook: Ringkasan, Penjualan, Detail Item, Produk Terlaris, Kas, Stok.
 *
 * Cara pakai (dari controller):
 *   (new ExcelReport($from, $to, 'Nama Usaha'))->download();
 */
class ExcelReport
{
    // Warna (format ARGB: FF + kode hex). Samakan dengan tema aplikasi.
    private const ACCENT      = 'FF0F766E';
    private const ACCENT_SOFT = 'FFE6F4F2';
    private const INK         = 'FF101828';
    private const MUTED       = 'FF667085';
    private const BORDER      = 'FFE4E7EC';
    private const ZEBRA       = 'FFF7F9FA';

    // Format angka Excel. Bagian ke-3 (setelah titik koma kedua) = tampilan saat nilai 0.
    private const FMT_RP   = '"Rp" #,##0;[Red]-"Rp" #,##0;"-"';
    private const FMT_NUM  = '#,##0;[Red]-#,##0;"-"';
    private const FMT_PCT  = '0.0%';
    private const FMT_DATE = 'dd/mm/yyyy hh:mm';

    // Warna label status: teks => [warna huruf, warna latar]
    private const BADGES = [
        'Lunas'       => ['FF15803D', 'FFDCFCE7'],
        'Aman'        => ['FF15803D', 'FFDCFCE7'],
        'Masuk'       => ['FF15803D', 'FFDCFCE7'],
        'Belum lunas' => ['FFB45309', 'FFFEF3C7'],
        'Menipis'     => ['FFB45309', 'FFFEF3C7'],
        'Keluar'      => ['FFB91C1C', 'FFFEE2E2'],
        'Habis'       => ['FFB91C1C', 'FFFEE2E2'],
    ];

    private \PDO $db;

    public function __construct(private string $from, private string $to, private string $business)
    {
        $this->db = db();
    }

    // ------------------------------------------------------------------
    // PUBLIK
    // ------------------------------------------------------------------

    public function build(): Spreadsheet
    {
        $wb = new Spreadsheet();
        $wb->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $wb->getProperties()
            ->setCreator($this->business)
            ->setTitle('Laporan Usaha ' . $this->business)
            ->setSubject('Laporan penjualan, kas, dan stok')
            ->setDescription('Dibuat otomatis oleh UMKM Toolkit');

        $this->summary($wb->getActiveSheet());
        $this->sales($wb->createSheet());
        $this->items($wb->createSheet());
        $this->topProducts($wb->createSheet());
        $this->cash($wb->createSheet());
        $this->stock($wb->createSheet());

        $wb->setActiveSheetIndex(0);
        return $wb;
    }

    /** Kirim file ke browser sebagai unduhan. */
    public function download(): never
    {
        $wb   = $this->build();
        $name = "Laporan_{$this->from}_sd_{$this->to}.xlsx";

        while (ob_get_level() > 0) {
            ob_end_clean(); // pastikan tidak ada output lain yang merusak file
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($wb))->save('php://output');
        $wb->disconnectWorksheets();
        exit;
    }

    // ------------------------------------------------------------------
    // SHEET 1: RINGKASAN
    // ------------------------------------------------------------------

    private function summary(Worksheet $ws): void
    {
        $p = $this->p();

        $sales    = $this->value('SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(sale_date) BETWEEN :from AND :to', $p);
        $cogs     = $this->value('SELECT COALESCE(SUM(si.cost*si.qty),0) FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE DATE(s.sale_date) BETWEEN :from AND :to', $p);
        $count    = $this->value('SELECT COUNT(*) FROM sales WHERE DATE(sale_date) BETWEEN :from AND :to', $p);
        $purchase = $this->value('SELECT COALESCE(SUM(total),0) FROM purchases WHERE DATE(purchase_date) BETWEEN :from AND :to', $p);
        $income   = $this->value("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='income' AND DATE(transaction_date) BETWEEN :from AND :to", $p);
        $expense  = $this->value("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='expense' AND DATE(transaction_date) BETWEEN :from AND :to", $p);
        $recv     = $this->value("SELECT COALESCE(SUM(amount-paid),0) FROM receivables WHERE status='open'");
        $pay      = $this->value("SELECT COALESCE(SUM(amount-paid),0) FROM payables WHERE status='open'");
        $stock    = $this->value('SELECT COALESCE(SUM(stock*buy_price),0) FROM products');

        // [kunci, label, nilai atau rumus, tipe, keterangan]. {kunci} di rumus = sel nilai baris tersebut.
        $items = [
            ['sales',     'Total penjualan',        $sales,    'rp',  'Seluruh transaksi penjualan pada periode'],
            ['cogs',      'Modal barang terjual',   $cogs,     'rp',  'Harga beli dikali jumlah yang terjual'],
            ['profit',    'Laba kotor',             '={sales}-{cogs}', 'rp',  'Penjualan dikurangi modal barang terjual'],
            ['margin',    'Margin laba',            '=IF({sales}=0,0,{profit}/{sales})', 'pct', 'Laba kotor dibagi penjualan'],
            ['count',     'Jumlah transaksi',       $count,    'num', 'Banyaknya faktur penjualan'],
            ['avg',       'Rata-rata per transaksi', '=IF({count}=0,0,{sales}/{count})', 'rp', 'Penjualan dibagi jumlah transaksi'],
            ['purchases', 'Pembelian stok',         $purchase, 'rp',  'Total nilai pembelian pada periode'],
            ['income',    'Pemasukan kas',          $income,   'rp',  'Termasuk pelunasan piutang'],
            ['expense',   'Pengeluaran kas',        $expense,  'rp',  'Termasuk pembelian stok, bayar hutang, dan biaya operasional'],
            ['net',       'Kas bersih',             '={income}-{expense}', 'rp', 'Pemasukan dikurangi pengeluaran'],
            ['recv',      'Piutang belum lunas',    $recv,     'rp',  'Posisi hari ini, bukan per periode'],
            ['pay',       'Hutang belum lunas',     $pay,      'rp',  'Posisi hari ini, bukan per periode'],
            ['stock',     'Nilai persediaan',       $stock,    'rp',  'Stok dikali harga beli, posisi hari ini'],
        ];

        $ws->setTitle('Ringkasan');
        $ws->getTabColor()->setARGB(self::ACCENT);
        $ws->setShowGridlines(false);
        $this->heading($ws, 'Ringkasan Laporan', 'C', $this->periodText());
        $this->headerRow($ws, 4, ['Indikator', 'Nilai', 'Keterangan']);
        $ws->getColumnDimension('A')->setWidth(30);
        $ws->getColumnDimension('B')->setWidth(22);
        $ws->getColumnDimension('C')->setWidth(58);

        // petakan kunci -> nomor baris supaya rumus bisa merujuk sel yang benar
        $rowOf = [];
        foreach ($items as $i => $it) {
            $rowOf[$it[0]] = 5 + $i;
        }

        $formats = ['rp' => self::FMT_RP, 'num' => self::FMT_NUM, 'pct' => self::FMT_PCT];
        foreach ($items as $i => [$key, $label, $val, $type, $note]) {
            $r = 5 + $i;
            $ws->setCellValueExplicit("A{$r}", $label, DataType::TYPE_STRING);
            if (is_string($val)) {
                $val = preg_replace_callback('/\{(\w+)\}/', fn($m) => 'B' . $rowOf[$m[1]], $val);
            }
            $ws->setCellValue("B{$r}", $val);
            $ws->getStyle("B{$r}")->getNumberFormat()->setFormatCode($formats[$type]);
            $ws->setCellValueExplicit("C{$r}", $note, DataType::TYPE_STRING);
            $ws->getRowDimension($r)->setRowHeight(22);
        }
        $last = 4 + count($items);

        $body = $ws->getStyle("A5:C{$last}");
        $body->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::BORDER);
        $body->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getStyle("A5:A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $ws->getStyle("B5:B{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle("B5:B{$last}")->getFont()->setBold(true);
        $ws->getStyle("C5:C{$last}")->getFont()->setColor($this->color(self::MUTED));
        $ws->getStyle("C5:C{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);

        // sorot baris yang paling penting
        foreach (['profit', 'net'] as $key) {
            $r = $rowOf[$key];
            $hl = $ws->getStyle("A{$r}:C{$r}");
            $hl->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ACCENT_SOFT);
            $hl->getFont()->setBold(true);
        }

        $this->pageSetup($ws, 3, 4);
    }

    // ------------------------------------------------------------------
    // SHEET 2-6: TABEL DATA
    // ------------------------------------------------------------------

    private function sales(Worksheet $ws): void
    {
        $data = $this->rows(
            "SELECT s.invoice_no, s.sale_date, COALESCE(c.name,'Umum') AS customer, s.payment_method, s.subtotal, s.discount, s.total, s.paid
             FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
             WHERE DATE(s.sale_date) BETWEEN :from AND :to ORDER BY s.sale_date, s.id",
            $this->p()
        );
        $methods = ['cash' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS', 'credit' => 'Kredit'];

        $rows = [];
        foreach ($data as $i => $d) {
            $rows[] = [
                $i + 1, $d['invoice_no'], $d['sale_date'], $d['customer'],
                $methods[$d['payment_method']] ?? $d['payment_method'],
                $d['subtotal'], $d['discount'], $d['total'], $d['paid'],
                '=H{r}-I{r}',   // Sisa = Total - Dibayar
                $d['paid'] >= $d['total'] ? 'Lunas' : 'Belum lunas',
            ];
        }

        $this->render($ws, 'Penjualan', 'Daftar Penjualan', $this->periodText(), 'FF2563EB', [
            ['h' => 'No',        'w' => 6,  't' => 'num', 'a' => 'c'],
            ['h' => 'Invoice',   'w' => 20, 't' => 'text'],
            ['h' => 'Tanggal',   'w' => 18, 't' => 'date'],
            ['h' => 'Pelanggan', 'w' => 26, 't' => 'text'],
            ['h' => 'Metode',    'w' => 12, 't' => 'text', 'a' => 'c'],
            ['h' => 'Subtotal',  'w' => 16, 't' => 'rp'],
            ['h' => 'Diskon',    'w' => 14, 't' => 'rp'],
            ['h' => 'Total',     'w' => 16, 't' => 'rp'],
            ['h' => 'Dibayar',   'w' => 16, 't' => 'rp'],
            ['h' => 'Sisa',      'w' => 16, 't' => 'rp'],
            ['h' => 'Status',    'w' => 14, 't' => 'text', 'a' => 'c'],
        ], $rows, [5, 6, 7, 8, 9], [10]);
    }

    private function items(Worksheet $ws): void
    {
        $data = $this->rows(
            "SELECT s.invoice_no, s.sale_date, si.product_name, si.qty, si.price, si.cost, si.subtotal
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             WHERE DATE(s.sale_date) BETWEEN :from AND :to ORDER BY s.sale_date, s.id, si.id",
            $this->p()
        );

        $rows = [];
        foreach ($data as $i => $d) {
            $rows[] = [$i + 1, $d['invoice_no'], $d['sale_date'], $d['product_name'], $d['qty'], $d['price'], $d['cost'], $d['subtotal'], '=H{r}-G{r}*E{r}'];
        }

        $this->render($ws, 'Detail Item', 'Rincian Item Terjual', $this->periodText(), 'FF7C3AED', [
            ['h' => 'No',         'w' => 6,  't' => 'num', 'a' => 'c'],
            ['h' => 'Invoice',    'w' => 20, 't' => 'text'],
            ['h' => 'Tanggal',    'w' => 18, 't' => 'date'],
            ['h' => 'Produk',     'w' => 34, 't' => 'text'],
            ['h' => 'Qty',        'w' => 9,  't' => 'num'],
            ['h' => 'Harga jual', 'w' => 16, 't' => 'rp'],
            ['h' => 'Modal',      'w' => 16, 't' => 'rp'],
            ['h' => 'Subtotal',   'w' => 16, 't' => 'rp'],
            ['h' => 'Laba',       'w' => 16, 't' => 'rp'],
        ], $rows, [4, 7, 8]);
    }

    private function topProducts(Worksheet $ws): void
    {
        $data = $this->rows(
            "SELECT si.product_name AS name, SUM(si.qty) AS qty, SUM(si.subtotal) AS revenue, SUM(si.subtotal - si.cost*si.qty) AS profit
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             WHERE DATE(s.sale_date) BETWEEN :from AND :to
             GROUP BY si.product_name ORDER BY revenue DESC",
            $this->p()
        );

        $rows = [];
        foreach ($data as $i => $d) {
            $rows[] = [$i + 1, $d['name'], $d['qty'], $d['revenue'], $d['profit'], '=IF(D{r}=0,0,E{r}/D{r})'];
        }

        $this->render($ws, 'Produk Terlaris', 'Produk Terlaris', $this->periodText(), 'FFF59E0B', [
            ['h' => 'No',     'w' => 6,  't' => 'num', 'a' => 'c'],
            ['h' => 'Produk', 'w' => 36, 't' => 'text'],
            ['h' => 'Qty',    'w' => 10, 't' => 'num'],
            ['h' => 'Omzet',  'w' => 18, 't' => 'rp'],
            ['h' => 'Laba',   'w' => 18, 't' => 'rp'],
            ['h' => 'Margin', 'w' => 12, 't' => 'pct'],
        ], $rows, [2, 3, 4]);
    }

    private function cash(Worksheet $ws): void
    {
        $data = $this->rows(
            "SELECT transaction_date, type, category, description, amount
             FROM cash_transactions WHERE DATE(transaction_date) BETWEEN :from AND :to ORDER BY transaction_date, id",
            $this->p()
        );

        $rows = [];
        foreach ($data as $i => $d) {
            $in = $d['type'] === 'income';
            $rows[] = [
                $i + 1, $d['transaction_date'], $in ? 'Masuk' : 'Keluar', $d['category'], $d['description'] ?? '',
                $in ? $d['amount'] : 0,
                $in ? 0 : $d['amount'],
                $i === 0 ? '=F{r}-G{r}' : '=H{p}+F{r}-G{r}',   // saldo berjalan
            ];
        }

        $this->render($ws, 'Kas', 'Buku Kas', $this->periodText(), 'FF16A34A', [
            ['h' => 'No',            'w' => 6,  't' => 'num', 'a' => 'c'],
            ['h' => 'Tanggal',       'w' => 18, 't' => 'date'],
            ['h' => 'Jenis',         'w' => 11, 't' => 'text', 'a' => 'c'],
            ['h' => 'Kategori',      'w' => 22, 't' => 'text'],
            ['h' => 'Keterangan',    'w' => 38, 't' => 'text'],
            ['h' => 'Masuk',         'w' => 17, 't' => 'rp'],
            ['h' => 'Keluar',        'w' => 17, 't' => 'rp'],
            ['h' => 'Saldo periode', 'w' => 18, 't' => 'rp'],
        ], $rows, [5, 6], [2]);
    }

    private function stock(Worksheet $ws): void
    {
        $data = $this->rows("SELECT sku, name, COALESCE(category,'-') AS category, unit, stock, min_stock, buy_price, sell_price FROM products ORDER BY name");

        $rows = [];
        foreach ($data as $i => $d) {
            $status = $d['stock'] <= 0 ? 'Habis' : ($d['stock'] <= $d['min_stock'] ? 'Menipis' : 'Aman');
            $rows[] = [$i + 1, $d['sku'], $d['name'], $d['category'], $d['unit'], $d['stock'], $d['min_stock'], $d['buy_price'], $d['sell_price'], '=F{r}*H{r}', $status];
        }

        $subtitle = $this->business . '  •  Posisi stok per ' . tgl(null, true);
        $this->render($ws, 'Stok', 'Posisi Stok', $subtitle, 'FFDC2626', [
            ['h' => 'No',          'w' => 6,  't' => 'num', 'a' => 'c'],
            ['h' => 'SKU',         'w' => 14, 't' => 'text'],
            ['h' => 'Produk',      'w' => 34, 't' => 'text'],
            ['h' => 'Kategori',    'w' => 16, 't' => 'text'],
            ['h' => 'Satuan',      'w' => 10, 't' => 'text', 'a' => 'c'],
            ['h' => 'Stok',        'w' => 9,  't' => 'num'],
            ['h' => 'Min.',        'w' => 9,  't' => 'num'],
            ['h' => 'Harga beli',  'w' => 16, 't' => 'rp'],
            ['h' => 'Harga jual',  'w' => 16, 't' => 'rp'],
            ['h' => 'Nilai stok',  'w' => 18, 't' => 'rp'],
            ['h' => 'Status',      'w' => 12, 't' => 'text', 'a' => 'c'],
        ], $rows, [5, 9], [10]);
    }

    // ------------------------------------------------------------------
    // PENATA TABEL (dipakai semua sheet data)
    // ------------------------------------------------------------------

    /**
     * @param array $cols       [['h' => judul, 'w' => lebar, 't' => text|num|rp|pct|date, 'a' => l|c|r], ...]
     * @param array $rows       baris data; nilai berawalan "=" dianggap rumus ({r} = baris ini, {p} = baris sebelumnya)
     * @param array $totals     indeks kolom yang dijumlahkan di baris Total (SUBTOTAL, ikut filter)
     * @param array $statusCols indeks kolom yang diberi warna label (Lunas, Masuk, dst)
     */
    private function render(Worksheet $ws, string $name, string $title, string $subtitle, string $tab, array $cols, array $rows, array $totals = [], array $statusCols = []): void
    {
        $ws->setTitle($name);
        $ws->getTabColor()->setARGB($tab);
        $ws->setShowGridlines(false);

        $n       = count($cols);
        $lastCol = Coordinate::stringFromColumnIndex($n);
        $hr      = 4; // baris header
        $first   = $hr + 1;

        $this->heading($ws, $title, $lastCol, $subtitle);
        $this->headerRow($ws, $hr, array_column($cols, 'h'));
        foreach ($cols as $i => $c) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($c['w']);
        }

        // Tidak ada data: tampilkan pesan, bukan tabel kosong
        if (!$rows) {
            $ws->mergeCells("A{$first}:{$lastCol}{$first}");
            $ws->setCellValueExplicit("A{$first}", 'Tidak ada data pada periode ini.', DataType::TYPE_STRING);
            $ws->getStyle("A{$first}")->applyFromArray([
                'font'      => ['italic' => true, 'color' => ['argb' => self::MUTED]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension($first)->setRowHeight(36);
            $this->pageSetup($ws, $n, $hr);
            return;
        }

        // 1) isi sel
        $r = $first;
        foreach ($rows as $row) {
            foreach ($cols as $i => $c) {
                $coord = Coordinate::stringFromColumnIndex($i + 1) . $r;
                $v     = $row[$i];
                if (is_string($v) && str_starts_with($v, '=')) {
                    $ws->setCellValue($coord, str_replace(['{r}', '{p}'], [(string) $r, (string) ($r - 1)], $v));
                } elseif ($c['t'] === 'date') {
                    $ws->setCellValue($coord, Date::PHPToExcel(strtotime((string) $v)));
                } elseif ($c['t'] === 'text') {
                    // dipaksa teks: aman dari rumus nyasar & nol di depan (mis. SKU) tidak hilang
                    $ws->setCellValueExplicit($coord, (string) $v, DataType::TYPE_STRING);
                } else {
                    $ws->setCellValue($coord, (float) $v);
                }
            }
            $r++;
        }
        $last = $r - 1;

        // 2) format & perataan per kolom
        $fmt   = ['rp' => self::FMT_RP, 'num' => self::FMT_NUM, 'pct' => self::FMT_PCT, 'date' => self::FMT_DATE];
        $align = ['l' => Alignment::HORIZONTAL_LEFT, 'c' => Alignment::HORIZONTAL_CENTER, 'r' => Alignment::HORIZONTAL_RIGHT];
        foreach ($cols as $i => $c) {
            $L  = Coordinate::stringFromColumnIndex($i + 1);
            $st = $ws->getStyle("{$L}{$first}:{$L}{$last}");
            if (isset($fmt[$c['t']])) {
                $st->getNumberFormat()->setFormatCode($fmt[$c['t']]);
            }
            $default = $c['t'] === 'text' ? 'l' : ($c['t'] === 'date' ? 'c' : 'r');
            $st->getAlignment()->setHorizontal($align[$c['a'] ?? $default]);
            if (($c['a'] ?? $default) === 'l') {
                $st->getAlignment()->setIndent(1);
            }
        }

        // 3) garis tabel + selang-seling warna baris
        $body = $ws->getStyle("A{$first}:{$lastCol}{$last}");
        $body->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::BORDER);
        $body->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $body->getFont()->setColor($this->color(self::INK));
        for ($z = $first + 1; $z <= $last; $z += 2) {
            $ws->getStyle("A{$z}:{$lastCol}{$z}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ZEBRA);
        }

        // 4) warna label status (Lunas hijau, Belum lunas kuning, dst) lewat conditional formatting
        foreach ($statusCols as $idx) {
            $L = Coordinate::stringFromColumnIndex($idx + 1);
            $conds = [];
            foreach (self::BADGES as $text => [$font, $bg]) {
                $cond = new Conditional();
                $cond->setConditionType(Conditional::CONDITION_CELLIS);
                $cond->setOperatorType(Conditional::OPERATOR_EQUAL);
                $cond->addCondition('"' . $text . '"');
                $cond->getStyle()->getFont()->setBold(true)->getColor()->setARGB($font);
                $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                $cond->getStyle()->getFill()->getEndColor()->setARGB($bg);
                $conds[] = $cond;
            }
            $ws->getStyle("{$L}{$first}:{$L}{$last}")->setConditionalStyles($conds);
        }

        // 5) baris Total (SUBTOTAL: ikut berubah saat data difilter)
        if ($totals) {
            $t = $last + 1;
            $ws->setCellValueExplicit("A{$t}", 'Total', DataType::TYPE_STRING);
            foreach ($totals as $idx) {
                $L = Coordinate::stringFromColumnIndex($idx + 1);
                $ws->setCellValue("{$L}{$t}", "=SUBTOTAL(109,{$L}{$first}:{$L}{$last})");
                $ws->getStyle("{$L}{$t}")->getNumberFormat()->setFormatCode($fmt[$cols[$idx]['t']]);
                $ws->getStyle("{$L}{$t}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $tr = $ws->getStyle("A{$t}:{$lastCol}{$t}");
            $tr->getFont()->setBold(true)->getColor()->setARGB(self::ACCENT);
            $tr->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ACCENT_SOFT);
            $tr->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::ACCENT);
            $tr->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $ws->getRowDimension($t)->setRowHeight(24);
        }

        // 6) filter di header + header ikut terkunci saat scroll
        $ws->setAutoFilter("A{$hr}:{$lastCol}{$last}");
        $ws->freezePane('A' . $first);
        $this->pageSetup($ws, $n, $hr);
    }

    // ------------------------------------------------------------------
    // HELPER TAMPILAN
    // ------------------------------------------------------------------

    /** Baris 1-3: judul besar, sub-judul (usaha, periode, tanggal dibuat), spasi. */
    private function heading(Worksheet $ws, string $title, string $lastCol, string $subtitle): void
    {
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->setCellValueExplicit('A1', $title, DataType::TYPE_STRING);
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => self::ACCENT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(30);

        $ws->mergeCells("A2:{$lastCol}2");
        $ws->setCellValueExplicit('A2', $subtitle, DataType::TYPE_STRING);
        $ws->getStyle('A2')->applyFromArray(['font' => ['size' => 10, 'color' => ['argb' => self::MUTED]]]);

        $ws->getRowDimension(3)->setRowHeight(8);
    }

    /** Baris header tabel: latar teal, huruf putih tebal, rata tengah. */
    private function headerRow(Worksheet $ws, int $row, array $labels): void
    {
        $last = Coordinate::stringFromColumnIndex(count($labels));
        foreach ($labels as $i => $label) {
            $ws->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1) . $row, (string) $label, DataType::TYPE_STRING);
        }
        $ws->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::ACCENT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::ACCENT]]],
        ]);
        $ws->getRowDimension($row)->setRowHeight(28);
    }

    /** Pengaturan cetak: A4, muat lebar 1 halaman, header tabel diulang, nomor halaman di footer. */
    private function pageSetup(Worksheet $ws, int $cols, int $headerRow): void
    {
        $ps = $ws->getPageSetup();
        $ps->setPaperSize(PageSetup::PAPERSIZE_A4);
        $ps->setOrientation($cols > 6 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
        $ps->setFitToWidth(1);
        $ps->setFitToHeight(0);
        $ps->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $ws->getPageMargins()->setLeft(0.4)->setRight(0.4);
        $ws->getHeaderFooter()->setOddFooter('&L' . str_replace('&', '&&', $this->business) . '&RHalaman &P dari &N');
    }

    private function periodText(): string
    {
        return $this->business . '  •  Periode ' . tgl($this->from) . ' – ' . tgl($this->to) . '  •  Dibuat ' . tgl(null, true);
    }

    private function color(string $argb): \PhpOffice\PhpSpreadsheet\Style\Color
    {
        return new \PhpOffice\PhpSpreadsheet\Style\Color($argb);
    }

    // ------------------------------------------------------------------
    // HELPER DATABASE
    // ------------------------------------------------------------------

    private function p(): array
    {
        return ['from' => $this->from, 'to' => $this->to];
    }

    private function rows(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function value(string $sql, array $params = []): float
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }
}