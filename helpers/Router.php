<?php
/**
 * Router — GET/POST + path parameters เช่น /inbox/:id
 */
declare(strict_types=1);

namespace App\Helpers;

use ReflectionMethod;

final class Router
{
    /**
     * @var array<string, array<string, array{0: class-string, 1: string}>>
     */
    private array $staticRoutes = [];

    /**
     * @var list<array{methods: string[], regex: string, paramNames: string[], class: class-string, action: string}>
     */
    private array $dynamicRoutes = [];

    private string $basePath = '';

    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $pathTemplate, string $controller, string $action): void
    {
        $this->addRoute('GET', $pathTemplate, $controller, $action);
    }

    public function post(string $pathTemplate, string $controller, string $action): void
    {
        $this->addRoute('POST', $pathTemplate, $controller, $action);
    }

    private function addRoute(string $method, string $pathTemplate, string $controller, string $action): void
    {
        $method = strtoupper($method);
        if (!str_contains($pathTemplate, ':')) {
            $p = $this->normalize($pathTemplate);
            $this->staticRoutes[$method][$p] = [$controller, $action];
            return;
        }
        $built = $this->buildDynamicPattern($pathTemplate);
        $this->dynamicRoutes[] = [
            'methods' => [$method],
            'regex' => $built['regex'],
            'paramNames' => $built['paramNames'],
            'class' => $controller,
            'action' => $action,
        ];
    }

    /**
     * @return array{regex: string, paramNames: string[]}
     */
    private function buildDynamicPattern(string $pathTemplate): array
    {
        $pathTemplate = '/' . trim($pathTemplate, '/');
        $segments = $pathTemplate === '/' ? [] : explode('/', trim($pathTemplate, '/'));
        $regex = '#^';
        $paramNames = [];
        foreach ($segments as $seg) {
            if (preg_match('/^:([a-zA-Z_][a-zA-Z0-9_]*)$/', $seg, $m)) {
                $regex .= '/([^/]+)';
                $paramNames[] = $m[1];
            } else {
                $regex .= '/' . preg_quote($seg, '#');
            }
        }
        $regex .= '$#';
        return ['regex' => $regex, 'paramNames' => $paramNames];
    }

    public function dispatch(string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath)) ?: '/';
        }
        $path = $this->normalize($path);
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (isset($this->staticRoutes[$method][$path])) {
            [$class, $action] = $this->staticRoutes[$method][$path];
            $this->invoke($class, $action, []);
            return;
        }

        foreach ($this->dynamicRoutes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $args = [];
            $i = 1;
            foreach ($route['paramNames'] as $name) {
                $args[$name] = $matches[$i] ?? null;
                $i++;
            }
            $this->invoke($route['class'], $route['action'], $args);
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'ไม่พบหน้า']);
    }

    /**
     * @param array<string, string|null> $routeArgs
     */
    private function invoke(string $class, string $action, array $routeArgs): void
    {
        $ctrl = new $class();
        $ref = new ReflectionMethod($class, $action);
        $invokeArgs = [];
        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $routeArgs) && $routeArgs[$name] !== null) {
                $invokeArgs[] = $routeArgs[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $invokeArgs[] = $param->getDefaultValue();
            } else {
                $invokeArgs[] = null;
            }
        }
        $ref->invokeArgs($ctrl, $invokeArgs);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
