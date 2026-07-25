<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'AniTech') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('figures/anitech-logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('figures/anitech-logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/guest.css', 'resources/js/inertia.js'])
    @inertiaHead
</head>
<body class="min-h-screen bg-stone-100 font-[Poppins] text-stone-900 antialiased">
    @inertia
</body>
</html>
