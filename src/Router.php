<?php
namespace App;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    private function normalize(string $path): string
    {
        if ($path === '') return '/';
        return '/' . trim($path, '/');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalize(rawurldecode($path));

        $routes = $this->routes[$method] ?? [];

        // Try exact match first
        if (isset($routes[$path])) {
            $this->call($routes[$path], []);
            return;
        }

        // Parameter routes: /reports/{id}
        foreach ($routes as $pattern => $handler) {
            if (!str_contains($pattern, '{')) continue;
            $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $m)) {
                $params = array_filter($m, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                $this->call($handler, $params);
                return;
            }
        }

        http_response_code(404);
        view_raw('errors/404', ['path' => $path]);
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $obj = new $class();
            $obj->{$method}(...array_values($params));
        } else {
            $handler(...array_values($params));
        }
    }
}
