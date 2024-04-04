<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ config('app.name', 'Laravel') }}</title>

    <x-laravel-exceptions-renderer::scripts :$exception />
    <x-laravel-exceptions-renderer::styles :$exception />
</head>
<body class="font-sans antialiased">
    {{ $slot }}
</body>
</html>
