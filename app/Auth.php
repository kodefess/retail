<?php
namespace App;

/**
 * Autentikasi: login, logout, dan membaca user yang sedang masuk.
 * Data user disimpan di $_SESSION['user'].
 */
class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /** Simpan user ke sesi. session_regenerate_id mencegah serangan session fixation. */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'business' => $user['business_name'],
        ];
    }

    public static function attempt(string $email, string $password): bool
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            self::login($user);
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /** Batasi percobaan login: maks 5 kali gagal, lalu tunggu 60 detik. */
    public static function throttled(): bool
    {
        $t = $_SESSION['login_fail'] ?? ['count' => 0, 'time' => 0];
        if ($t['count'] >= 5 && time() - $t['time'] < 60) {
            return true;
        }
        if (time() - $t['time'] >= 60) {
            unset($_SESSION['login_fail']);
        }
        return false;
    }

    public static function recordFailure(): void
    {
        $t = $_SESSION['login_fail'] ?? ['count' => 0, 'time' => 0];
        $_SESSION['login_fail'] = ['count' => $t['count'] + 1, 'time' => time()];
    }
}