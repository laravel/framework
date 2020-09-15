<?php

// Check if the application is in maintenance mode...
if (! file_exists($down = __DIR__.'/down')) {
    return;
}

// Decode the "down" file's JSON...
$data = json_decode(file_get_contents($down), true);

// Allow framework to handle request if no prerendered template...
if (! isset($data['template'])) {
    return;
}

// Allow framework to handle maintenance mode bypass route...
if (isset($data['secret']) && $_SERVER['REQUEST_URI'] === '/'.$data['secret']) {
    return;
}

// Determine if maintenance mode bypass cookie is valid...
if (isset($_COOKIE['laravel_maintenance']) && isset($data['secret'])) {
    $payload = json_decode(base64_decode($_COOKIE['laravel_maintenance']), true);

    if (is_array($payload) &&
        is_numeric($payload['expires_at'] ?? null) &&
        isset($payload['mac']) &&
        hash_equals(hash_hmac('SHA256', $payload['expires_at'], $data['secret']), $payload['mac']) &&
        (int) $payload['expires_at'] >= time()) {
        return;
    }
}

// Redirect to the proper path if necessary...
if (isset($data['redirect']) && $_SERVER['REQUEST_URI'] !== $data['redirect']) {
    http_response_code(302);
    header('Location: '.$data['redirect']);

    exit;
}

// Output the prerendered template...
http_response_code($data['status'] ?? 503);
header('Retry-After: '.$data['retry']);

echo $data['template'];

exit;
