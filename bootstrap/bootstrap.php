<?php
/**
 * BOOTSTRAP: menyiapkan semua yang dibutuhkan sebelum aplikasi berjalan.
 */
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Baca file .env (DB_HOST, DB_PASSWORD, dst). safeLoad = tidak error jika .env tidak ada
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

date_default_timezone_set('Asia/Jakarta');

// Cookie sesi aman: tidak bisa dibaca JavaScript, dan tidak dikirim lintas situs
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}