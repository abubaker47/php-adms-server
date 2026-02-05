<?php

namespace ADMS\API;

class Router
{
    private $routes = [];
    private $middleware = [];

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function use($middleware)
    {
        $this->middleware[] = $middleware;
    }

    public function dispatch($method, $uri)
    {
        // Parse URI
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);
            if ($params !== false) {
                // Execute middleware
                foreach ($this->middleware as $mw) {
                    $result = call_user_func($mw);
                    if ($result === false) {
                        return;
                    }
                }

                // Execute handler
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // No route found
        $this->sendResponse(['error' => 'Not found'], 404);
    }

    private function matchRoute($pattern, $uri)
    {
        // Convert pattern to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    public function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function sendError($message, $statusCode = 400)
    {
        $this->sendResponse(['error' => $message], $statusCode);
    }

    public function getRequestBody()
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true);
    }

    public function getQueryParams()
    {
        return $_GET;
    }
}
