<?php

$publicPath = getcwd();

$uri = urldecode(
    (class_exists(\Uri\Rfc3986\Uri::class)
        ? \Uri\Rfc3986\Uri::parse($_SERVER['REQUEST_URI'])?->getPath()
        : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
