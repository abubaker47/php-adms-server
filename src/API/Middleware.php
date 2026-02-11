<?php

namespace ADMS\API;

class Middleware
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function authenticate()
    {
        if (!$this->config['security']['enable_auth']) {
            return true;
        }

        $headers = getallheaders();
        $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? null;

        if (!$apiKey || $apiKey !== $this->config['security']['api_key']) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return false;
        }

        return true;
    }

    public function cors()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return true;
    }

    public function validateRequest()
    {
        // Add request validation logic here
        return true;
    }
}
