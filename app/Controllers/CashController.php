<?php
namespace App\Controllers;

/** Buku kas: pemasukan & pengeluaran (manual + otomatis dari transaksi). */
class CashController
{
    public function index(): void
    {
        $from = input('from', date('Y-m-01'));
        $to   = input('to', date('Y-m-d'));
        $type = in_array(input('type'), ['income', 'expense'], true) ? input('type') : '';

        $sql    = 'SELECT * FROM cash_transactions WHERE DATE(transaction_date) BETWEEN :from AND :to';
        $params = ['from' => $from, 'to' => $to];
        if ($type !== '') {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        $stmt = db()->prepare($sql . ' ORDER BY transaction_date DESC, id DESC');
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        $period = ['income' => 0, 'expense' => 0];
        $sum = db()->prepare('SELECT type, SUM(amount) s FROM cash_transactions WHERE DATE(transaction_date) BETWEEN :from AND :to GROUP BY type');
        $sum->execute(['from' => $from, 'to' => $to]);
        foreach ($sum->fetchAll() as $r) $period[$r['type']] = (float) $r['s'];

        $balance = (float) db()->query("SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE -amount END),0) FROM cash_transactions")->fetchColumn();

        view('cash/index', [
            'title' => 'Kas', 'active' => 'cash',
            'transactions' => $transactions, 'balance' => $balance,
            'income' => $period['income'], 'expense' => $period['expense'],
            'from' => $from, 'to' => $to, 'type' => $type,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $amount = (float) input('amount', '0');
        if ($amount <= 0 || input('category') === '') {
            flash('danger', 'Kategori dan nominal (lebih dari 0) wajib diisi.');
            redirect('/cash');
        }

        $date = strtotime(input('transaction_date')) ?: time();
        db()->prepare('INSERT INTO cash_transactions (type, category, description, amount, transaction_date) VALUES (:t,:c,:d,:a,:dt)')
            ->execute([
                't'  => input('type') === 'expense' ? 'expense' : 'income',
                'c'  => input('category'),
                'd'  => input('description'),
                'a'  => $amount,
                'dt' => date('Y-m-d H:i:s', $date),
            ]);
        flash('success', 'Transaksi kas dicatat.');
        redirect('/cash');
    }

    /** Hanya transaksi manual yang boleh dihapus; kas otomatis (ref_type terisi) dikunci. */
    public function destroy(int $id): void
    {
        verify_csrf();
        $stmt = db()->prepare('DELETE FROM cash_transactions WHERE id = :id AND ref_type IS NULL');
        $stmt->execute(['id' => $id]);
        flash($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'Transaksi dihapus.' : 'Kas otomatis dari penjualan/pembelian tidak bisa dihapus.');
        redirect('/cash');
    }
}