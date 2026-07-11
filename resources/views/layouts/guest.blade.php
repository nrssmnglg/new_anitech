<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', 'Guest')) }} | AniTech</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
    @unless (app()->runningUnitTests())
        @vite('resources/css/guest.css')
    @endunless
</head>
<body class="min-h-screen bg-stone-100 font-[Poppins] text-stone-900 antialiased">
    @yield('body')

    @unless (app()->runningUnitTests())
        @stack('scripts')
    @endunless
</body>
</html>
