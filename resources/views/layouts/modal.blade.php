<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', 'Admin Modal')) }}</title>
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset('figures/anitech-mark-official.svg') }}?v=3">
    <link rel="shortcut icon" href="{{ asset('figures/anitech-mark-official.svg') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('figures/anitech-mark-official.svg') }}?v=3">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
    @unless (app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
</head>
<body class="bg-slate-50 text-slate-900">
    <main class="mx-auto w-full max-w-7xl px-5 py-5 sm:px-6 sm:py-6">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4 rounded-2xl border border-emerald-100 bg-white px-5 py-4 shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-[#1b4d3e]">@yield('title', 'Admin')</h1>
                @hasSection('subtitle')
                    <p class="mt-1 text-sm text-slate-500">@yield('subtitle')</p>
                @endif
            </div>
            @hasSection('header_actions')
                <div>
                    @yield('header_actions')
                </div>
            @endif
        </div>

        @if (session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
