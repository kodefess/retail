<?php
namespace App;

/**
 * ROUTER: mencocokkan URL + method (GET/POST) dengan fungsi di controller.
 * Contoh: $r->get('/products/{id}/edit', [$controller, 'edit'])
 * Bagian {id} akan dikirim sebagai argumen $id ke method edit().
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, bool $public = false): void
    {
        $this->add('GET', $path, $handler, $public);
    }

    public function post(string $path, array $handler, bool $public = false): void
    {
        $this->add('POST', $path, $handler, $public);
    }

    /** $public = true berarti halaman boleh diakses tanpa login (login/register). */
    private function add(string $method, string $path, array $handler, bool $public): void
    {
        // ubah {id} menjadi regex bernama: (?P<id>[^/]+)
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'public');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }

            // Belum login? tendang ke halaman login. Sudah login tapi buka /login? ke dashboard.
            if (!$route['public'] && !Auth::check()) {
                redirect('/login');
            }
            if ($route['public'] && Auth::check()) {
                redirect('/');
            }

            // ambil hanya parameter bernama (id, dst) dari hasil regex
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            call_user_func_array($route['handler'], $params);
            return;
        }

        http_response_code(404);
        view('errors/404', ['title' => 'Halaman tidak ditemukan'], Auth::check() ? 'app' : 'auth');
    }
}