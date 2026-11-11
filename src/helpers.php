<?php

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($page, $params = [])
{
    $query = http_build_query(array_merge(['page' => $page], $params));
    header('Location: index.php?' . $query);
    exit;
}

function json_response($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $code = 400)
{
    json_response(['ok' => false, 'error' => $message], $code);
}

function render($template, $vars = [])
{
    extract($vars);
    $user = Auth::user();
    ob_start();
    require __DIR__ . '/../templates/' . $template . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/../templates/layout.php';
}

function flash($message = null)
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $msg = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $msg;
}

function format_date($date)
{
    if (!$date) {
        return '—';
    }
    return date('d.m.Y', strtotime($date));
}

function plural($n, $one, $few, $many)
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 == 1) {
        return $one;
    }
    return $many;
}

function input($key, $default = null)
{
    if (isset($_POST[$key])) {
        return trim($_POST[$key]);
    }
    if (isset($_GET[$key])) {
        return trim($_GET[$key]);
    }
    return $default;
}

function request_json()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Старый вариант форматирования даты, оставлен для совместимости
function old_format_date($date)
{
    $parts = explode('-', substr($date, 0, 10));
    if (count($parts) != 3) {
        return $date;
    }
    return $parts[2] . '.' . $parts[1] . '.' . $parts[0];
}
