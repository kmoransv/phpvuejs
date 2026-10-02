<?php

class App
{
    private string $controllerClass = 'AutoresController';
    private string $method = 'index';
    private array $params = [];
    private $controllerInstance;
    private bool $dispatched = false;

    public function __construct()
    {
        session_start();
        $url = $this->parseUrl();

        // API: /api/contadores -> DashboardController@contadores
        if (!empty($url[0]) && $url[0] === 'api' && !empty($url[1]) && $url[1] === 'contadores') {
            require_once __DIR__ . '/../controllers/DashboardController.php';
            $this->controllerInstance = new DashboardController();
            $this->method = 'contadores';
            $this->params = array_slice($url, 2);
            $this->dispatched = true;
        }

        if (!empty($url[0])) {
            if ($this->dispatched) {
                // ya manejado arriba
            } elseif ($url[0] === 'api') {
                // otros endpoints api no implementados
            } else {
                $candidate = ucfirst($url[0]) . 'Controller';
                if (file_exists(__DIR__ . '/../controllers/' . $candidate . '.php')) {
                    $this->controllerClass = $candidate;
                    unset($url[0]);
                }
            }
        }

        if (!$this->dispatched) {
            require_once __DIR__ . '/../controllers/' . $this->controllerClass . '.php';
            $this->controllerInstance = new $this->controllerClass();

            if (!empty($url[1])) {
                if (method_exists($this->controllerInstance, $url[1])) {
                    $this->method = $url[1];
                    unset($url[0], $url[1]);
                } else {
                    unset($url[0]);
                }
            }

            $this->params = $url ? array_values($url) : [];
        }
    }
    public function run(): void
    {
        try {
            call_user_func_array([$this->controllerInstance, $this->method], $this->params);
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    private function parseUrl(): array
    {
        $url = $_GET['url'] ?? '';
        return explode('/', filter_var(trim($url, '/'), FILTER_SANITIZE_URL));
    }
}