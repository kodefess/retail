<?php
namespace App\Controllers;

/**
 * Piutang (pelanggan berutang ke kita) dan Hutang (kita berutang ke supplier).
 * Logikanya hampir sama, jadi satu controller dengan konfigurasi berbeda.
 */
class DebtController
{
    private array $config = [
        'receivables' => [
            'title' => 'Piutang', 'party_table' => 'customers', 'party_col' => 'customer_id',
            'ref_table' => 'sales', 'ref_col' => 'sale_id', 'ref_no' => 'invoice_no', 'ref_date' => 'sale_date',
            'cash_type' => 'income', 'cash_cat' => 'Pelunasan Piutang', 'party_label' => 'Pelanggan',
        ],
        'payables' => [
            'title' => 'Hutang', 'party_table' => 'suppliers', 'party_col' => 'supplier_id',
            'ref_table' => 'purchases', 'ref_col' => 'purchase_id', 'ref_no' => 'ref_no', 'ref_date' => 'purchase_date',
            'cash_type' => 'expense', 'cash_cat' => 'Bayar Hutang', 'party_label' => 'Supplier',
        ],
    ];

    public function __construct(private string $table)
    {
        if (!isset($this->config[$table])) {
            throw new \InvalidArgumentException('Tabel tidak valid');
        }
    }

    public function index(): void
    {
        $c      = $this->config[$this->table];
        $status = in_array(input('status'), ['open', 'paid', 'all'], true) ? input('status') : 'open';

        $sql = "SELECT d.*, p.name AS party_name, r.{$c['ref_no']} AS ref_no, r.{$c['ref_date']} AS ref_date
                FROM {$this->table} d
                LEFT JOIN {$c['party_table']} p ON p.id = d.{$c['party_col']}
                JOIN {$c['ref_table']} r ON r.id = d.{$c['ref_col']}";
        $params = [];
        if ($status !== 'all') {
            $sql .= ' WHERE d.status = :st';
            $params['st'] = $status;
        }
        $sql .= ' ORDER BY d.due_date IS NULL, d.due_date ASC, d.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $open = db()->query("SELECT COUNT(*) c, COALESCE(SUM(amount-paid),0) s FROM {$this->table} WHERE status='open'")->fetch();
        $overdue = (int) db()->query("SELECT COUNT(*) FROM {$this->table} WHERE status='open' AND due_date < CURDATE()")->fetchColumn();

        view('debts/index', [
            'title'    => $c['title'],
            'active'   => $this->table,
            'type'     => $this->table,
            'label'    => $c['title'],
            'partyLabel' => $c['party_label'],
            'rows'     => $rows,
            'status'   => $status,
            'openCount' => (int) $open['c'],
            'openSum'  => (float) $open['s'],
            'overdue'  => $overdue,
        ]);
    }

    /** Catat pembayaran (cicilan/pelunasan). Otomatis masuk buku kas. */
    public function pay(int $id): void
    {
        verify_csrf();
        $c      = $this->config[$this->table];
        $amount = (float) input('amount', '0');
        $pdo    = db();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $debt = $stmt->fetch();
            if (!$debt) throw new \RuntimeException('Data tidak ditemukan.');

            $remaining = (float) $debt['amount'] - (float) $debt['paid'];
            if ($amount <= 0)        throw new \RuntimeException('Nominal pembayaran harus lebih dari 0.');
            if ($amount > $remaining) throw new \RuntimeException('Nominal melebihi sisa (' . money($remaining) . ').');

            $newPaid = (float) $debt['paid'] + $amount;
            $pdo->prepare("UPDATE {$this->table} SET paid = :p, status = :s WHERE id = :id")
                ->execute(['p' => $newPaid, 's' => $newPaid >= (float) $debt['amount'] ? 'paid' : 'open', 'id' => $id]);

            // sinkronkan kolom "paid" di transaksi asal (sales/purchases)
            $pdo->prepare("UPDATE {$c['ref_table']} SET paid = paid + :a WHERE id = :rid")
                ->execute(['a' => $amount, 'rid' => $debt[$c['ref_col']]]);

            $pdo->prepare('INSERT INTO cash_transactions (type, category, description, amount, transaction_date, ref_type, ref_id) VALUES (:t,:c,:d,:a,:dt,:rt,:rid)')
                ->execute(['t' => $c['cash_type'], 'c' => $c['cash_cat'], 'd' => $c['cash_cat'] . ' #' . $id, 'a' => $amount, 'dt' => date('Y-m-d H:i:s'), 'rt' => $this->table, 'rid' => $id]);

            $pdo->commit();
            flash('success', 'Pembayaran ' . money($amount) . ' dicatat.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', $e instanceof \RuntimeException ? $e->getMessage() : 'Gagal mencatat pembayaran.');
        }
        redirect('/' . $this->table);
    }
}