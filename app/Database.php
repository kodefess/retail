<?php
namespace App;

use PDO;

/**
 * Koneksi database (PDO). Memakai pola "satu koneksi dipakai bersama"
 * supaya tidak membuka koneksi baru di setiap query.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $name = $_ENV['DB_DATABASE'] ?? 'umkm_toolkit';
            $user = $_ENV['DB_USERNAME'] ?? 'root';
            $pass = $_ENV['DB_PASSWORD'] ?? '';

            self::$pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // error SQL jadi exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // hasil berupa array asosiatif
                PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statement asli (lebih aman)
            ]);
            // Samakan zona waktu MySQL dengan PHP supaya CURDATE() = hari ini di WIB
            self::$pdo->exec("SET time_zone = '+07:00'");
        }
        return self::$pdo;
    }
}