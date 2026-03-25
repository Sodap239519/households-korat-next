<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'Households Korat') }}</title>
    @vite(['resources/css/app.css', 'resources/js/spa/main.js'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
