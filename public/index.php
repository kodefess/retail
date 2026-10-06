<?php
/**
 * FRONT CONTROLLER
 * Semua request masuk lewat file ini. Tugasnya hanya 3:
 * 1. Menyalakan aplikasi (bootstrap)
 * 2. Memuat daftar route
 * 3. Meminta Router mencocokkan URL dengan controller
 */
require __DIR__ . '/../bootstrap/bootstrap.php';

try {
    $router = require __DIR__ . '/../routes/web.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    http_response_code(500);
    $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    view('errors/500', [
        'title'   => 'Terjadi kesalahan',
        'message' => $debug ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : null,
    ], \App\Auth::check() ? 'app' : 'auth');
}