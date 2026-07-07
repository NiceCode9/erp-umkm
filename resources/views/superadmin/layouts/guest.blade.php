<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css'])
</head>
<body class="bg-muted text-foreground">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        @yield('content')
    </div>
</body>
</html>
