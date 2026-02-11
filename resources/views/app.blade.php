<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ZyraVision — Plataforma Profissional de Monitoramento CFTV Inteligente">
    <meta name="theme-color" content="#1A73E8">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <title inertia>{{ config('app.name', 'ZyraVision') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
