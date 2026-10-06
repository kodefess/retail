<?php
namespace App\Controllers;

/**
 * "Party" = pihak lain dalam bisnis: pelanggan atau supplier.
 * Satu controller untuk dua tabel; perbedaannya dikonfigurasi di $config.
 */
class PartyController
{
    private array $config = [
        'customers' => ['label' => 'Pelanggan', 'debt_table' => 'receivables', 'debt_col' => 'customer_id', 'debt_label' => 'Piutang'],
        'suppliers' => ['label' => 'Supplier',  'debt_table' => 'payables',    'debt_col' => 'supplier_id', 'debt_label' => 'Hutang'],
    ];

    public function __construct(private string $table)
    {
        // $table dipakai langsung di SQL, jadi HARUS dibatasi ke daftar yang diizinkan
        if (!isset($this->config[$table])) {
            throw new \InvalidArgumentException('Tabel tidak valid');
        }
    }

    public function index(): void
    {
        $c      = $this->config[$this->table];
        $search = input('search');

        // subquery menghitung sisa utang/piutang tiap pihak
        $sql = "SELECT p.*, COALESCE((SELECT SUM(d.amount - d.paid) FROM {$c['debt_table']} d WHERE d.{$c['debt_col']} = p.id AND d.status = 'open'), 0) AS debt
                FROM {$this->table} p";
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE p.name LIKE :q1 OR p.phone LIKE :q2';
            $params = ['q1' => "%$search%", 'q2' => "%$search%"];
        }
        $sql .= ' ORDER BY p.name ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        view('parties/index', [
            'title'  => $c['label'],
            'active' => $this->table,
            'type'   => $this->table,
            'label'  => $c['label'],
            'debtLabel' => $c['debt_label'],
            'items'  => $stmt->fetchAll(),
            'search' => $search,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        if (input('name') === '') {
            flash('danger', 'Nama wajib diisi.');
            redirect('/' . $this->table);
        }
        $stmt = db()->prepare("INSERT INTO {$this->table} (name, phone, address) VALUES (:n, :p, :a)");
        $stmt->execute(['n' => input('name'), 'p' => input('phone'), 'a' => input('address')]);
        flash('success', $this->config[$this->table]['label'] . ' ditambahkan.');
        redirect('/' . $this->table);
    }

    public function update(int $id): void
    {
        verify_csrf();
        if (input('name') === '') {
            flash('danger', 'Nama wajib diisi.');
            redirect('/' . $this->table);
        }
        $stmt = db()->prepare("UPDATE {$this->table} SET name=:n, phone=:p, address=:a WHERE id=:id");
        $stmt->execute(['n' => input('name'), 'p' => input('phone'), 'a' => input('address'), 'id' => $id]);
        flash('success', 'Data diperbarui.');
        redirect('/' . $this->table);
    }

    public function destroy(int $id): void
    {
        verify_csrf();
        db()->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute(['id' => $id]);
        flash('success', 'Data dihapus.');
        redirect('/' . $this->table);
    }
}