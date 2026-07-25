<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', 'Admin')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('figures/anitech-logo.png') }}">
    <link rel="alternate icon" href="{{ asset('figures/anitech-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('figures/anitech-logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet" />
    @unless (app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
    @stack('styles')
</head>
<body>
    @include('layouts.partials.sidebar')

    @php
        $adminUser = auth()->user();
        $officeProfile = $adminUser?->officeProfile;
        $topbarInitial = strtoupper(substr((string) ($adminUser?->name ?? 'A'), 0, 1));
        $topbarRoleLabel = $adminUser?->role === \App\Models\User::ROLE_ADMIN ? 'Admin Officer' : 'Staff Officer';
        $topbarRegionLabel = $officeProfile?->job_title ?: 'Region IV-A';
    @endphp

    <div class="admin-topbar-shell">
        <div class="admin-topbar">
            <div class="admin-topbar__search">
                <button
                    type="button"
                    class="admin-mobile-brand-toggle"
                    data-admin-sidebar-toggle
                    aria-label="Toggle navigation menu"
                    aria-controls="admin-sidebar"
                    aria-expanded="false"
                >
                    <img src="{{ asset('figures/anitech-mark-official.svg') }}" alt="" aria-hidden="true">
                    <span>AniTech</span>
                </button>

                <label class="admin-topbar__searchbox" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input
                        type="search"
                        placeholder="Search farmers, records, or files..."
                        aria-label="Search farmers, records, or files"
                    >
                </label>
            </div>

            <div class="admin-topbar__actions">
                <button type="button" class="admin-topbar__icon" aria-label="Alerts">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                        <path d="M10 21a2 2 0 0 0 4 0"></path>
                    </svg>
                    <span class="admin-topbar__dot" aria-hidden="true"></span>
                </button>

                <button type="button" class="admin-topbar__icon" aria-label="Messages">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 5h16v14H4z"></path>
                        <path d="m4 7 8 6 8-6"></path>
                    </svg>
                </button>

                <div class="admin-topbar__divider" aria-hidden="true"></div>

                <div class="admin-topbar__profile">
                    <div class="admin-topbar__avatar">
                        {{ $topbarInitial }}
                    </div>
                    <div class="admin-topbar__profile-copy">
                        <strong>{{ $topbarRoleLabel }}</strong>
                        <span>{{ $topbarRegionLabel }}</span>
                    </div>
                    <svg class="admin-topbar__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-topbar__logout">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <main class="main-wrapper">
        @hasSection('title')
            <div class="page-header page-header--compact">
                <div class="page-header__intro">
                    <h1 class="page-title">@yield('title', 'Admin')</h1>
                    @hasSection('subtitle')
                        <p class="page-subtitle">@yield('subtitle')</p>
                    @endif
                </div>
                @hasSection('header_actions')
                    <div class="page-header__actions">
                        <div>
                            @yield('header_actions')
                        </div>
                    </div>
                @endif
            </div>
        @endif

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
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
