<?php

class Controller
{
    public function model(string $model)
    {
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model();
    }

    public function view(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function redirect(string $url): void
    {
        $base = '/fdtw2-2026/periodo2/phpvuejs/public';
        header('Location: ' . $base . $url);
        exit;
    }

    public function input(string $key): ?string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : null;
    }

    public function inputRaw(string $key): ?string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        return $value !== null ? trim($value) : null;
    }

    public function flash(string $key, $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }

    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}