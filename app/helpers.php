<?php
/**
 * HELPER: fungsi kecil yang dipakai di mana-mana (view maupun controller).
 * File ini dimuat otomatis oleh Composer (lihat "autoload.files" di composer.json).
 */

/** Ambil koneksi database. Pemakaian: db()->query(...) */
function db(): PDO
{
    return \App\Database::connection();
}

/** Escape HTML agar aman dari XSS. Wajib dipakai untuk setiap data yang dicetak ke halaman. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format rupiah: 15000 -> "Rp 15.000" */
function money($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

/** Format angka biasa: 15000 -> "15.000" */
function num($value): string
{
    return number_format((float) $value, 0, ',', '.');
}

/** Format tanggal Indonesia: tgl('2026-10-06 14:30', true) -> "6 Okt 2026, 14:30" */
function tgl(?string $date = null, bool $withTime = false, bool $withDay = false): string
{
    $ts     = $date ? strtotime($date) : time();
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $days   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $out    = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    if ($withDay) {
        $out = $days[(int) date('w', $ts)] . ', ' . $out;
    }
    return $withTime ? $out . ', ' . date('H:i', $ts) : $out;
}

/** Inisial nama untuk avatar: "Budi Santoso" -> "BS" */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $out   = mb_substr($parts[0] ?? '', 0, 1);
    if (count($parts) > 1) {
        $out .= mb_substr(end($parts), 0, 1);
    }
    return mb_strtoupper($out);
}

/** Link WhatsApp dari nomor telepon: 0812xxx -> https://wa.me/62812xxx */
function wa_link(?string $phone, string $text = ''): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return null;
    }
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/** URL aset + versi (supaya browser mengambil file baru saat file berubah). */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/public/assets/' . $path;
    return '/assets/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Jumlah produk yang stoknya menipis (dipakai badge di sidebar). */
function low_stock_count(): int
{
    static $count = null;
    if ($count === null) {
        $count = (int) db()->query('SELECT COUNT(*) FROM products WHERE stock <= min_stock')->fetchColumn();
    }
    return $count;
}

// ---------- Keamanan: CSRF ----------

function csrf_token(): string
{
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

/** Cetak <input hidden> berisi token. Letakkan di setiap <form method="POST">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Panggil di awal setiap aksi POST. Menolak request yang tidak membawa token valid. */
function verify_csrf(): void
{
    $sent = $_POST['_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['_token'] ?? '', $sent)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}

// ---------- Flash message (pesan sekali tampil) ----------

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['t' => $type, 'm' => $message];
}

function getFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------- Alur halaman ----------

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Ambil input POST/GET yang sudah di-trim. */
function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/**
 * Tampilkan view di dalam layout.
 * view('products/index', ['products' => $rows]) -> views/products/index.php dibungkus views/layouts/app.php
 */
function view(string $name, array $data = [], string $layout = 'app'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__) . '/views/' . $name . '.php';
    require dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
}

/** Kirim respons JSON (untuk data chart). */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** json_encode yang aman ditaruh di dalam <script> */
function json_script($data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}