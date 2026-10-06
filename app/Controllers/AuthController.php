<?php
namespace App\Controllers;

use App\Auth;

/** Login, register, dan logout. */
class AuthController
{
    public function showLogin(): void
    {
        view('auth/login', ['title' => 'Masuk', 'errors' => [], 'old' => []], 'auth');
    }

    public function login(): void
    {
        verify_csrf();
        $email = strtolower(input('email'));
        $old   = ['email' => $email];

        if (Auth::throttled()) {
            view('auth/login', ['title' => 'Masuk', 'errors' => ['Terlalu banyak percobaan. Coba lagi dalam 1 menit.'], 'old' => $old], 'auth');
            return;
        }

        // password tidak di-trim: spasi bisa jadi bagian dari password
        if (Auth::attempt($email, (string) ($_POST['password'] ?? ''))) {
            unset($_SESSION['login_fail']);
            flash('success', 'Selamat datang kembali, ' . Auth::user()['name'] . '!');
            redirect('/');
        }

        Auth::recordFailure();
        view('auth/login', ['title' => 'Masuk', 'errors' => ['Email atau kata sandi salah.'], 'old' => $old], 'auth');
    }

    public function showRegister(): void
    {
        view('auth/register', ['title' => 'Daftar', 'errors' => [], 'old' => []], 'auth');
    }

    public function register(): void
    {
        verify_csrf();
        $old = [
            'name'          => input('name'),
            'business_name' => input('business_name'),
            'email'         => strtolower(input('email')),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirmation'] ?? '');

        $errors = [];
        if ($old['name'] === '')          $errors[] = 'Nama lengkap wajib diisi.';
        if ($old['business_name'] === '') $errors[] = 'Nama usaha wajib diisi.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
        if (strlen($password) < 8)        $errors[] = 'Kata sandi minimal 8 karakter.';
        if ($password !== $confirm)       $errors[] = 'Konfirmasi kata sandi tidak sama.';

        if (!$errors) {
            $cek = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $cek->execute(['email' => $old['email']]);
            if ($cek->fetchColumn() > 0) {
                $errors[] = 'Email sudah terdaftar. Silakan masuk.';
            }
        }

        if ($errors) {
            view('auth/register', ['title' => 'Daftar', 'errors' => $errors, 'old' => $old], 'auth');
            return;
        }

        $stmt = db()->prepare('INSERT INTO users (name, business_name, email, password) VALUES (:n, :b, :e, :p)');
        $stmt->execute([
            'n' => $old['name'],
            'b' => $old['business_name'],
            'e' => $old['email'],
            'p' => password_hash($password, PASSWORD_DEFAULT), // JANGAN simpan password polos
        ]);

        Auth::login(['id' => db()->lastInsertId(), 'name' => $old['name'], 'email' => $old['email'], 'business_name' => $old['business_name']]);
        flash('success', 'Akun berhasil dibuat. Mulai dengan menambahkan produk pertamamu.');
        redirect('/products/create');
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logout();
        session_start(); // sesi baru supaya flash message bisa dipakai
        flash('success', 'Kamu sudah keluar.');
        redirect('/login');
    }
}