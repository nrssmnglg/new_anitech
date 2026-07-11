<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#163f31">
    <title>AniTech Farmer PWA</title>
    @php
        $assetBase = url()->to(rtrim(str_replace('/index.php', '', request()->getBaseUrl()), '/'));
    @endphp
    <script>
        window.__FARMER_PWA__ = @json($shell);
        window.__FARMER_PWA__.assetBase = @json($assetBase);
    </script>
    @vite(['resources/css/pwa/shared.css', 'resources/css/farmer-app.css', 'resources/js/farmer-app/app.js'])
</head>
<body class="pwa-body">
    <div id="farmer-pwa-app"></div>
</body>
</html>
