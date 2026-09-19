<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name') }}</title>
    <script>
        (() => {
            const root = document.documentElement;
            let preference = 'system';

            try {
                const stored = window.localStorage.getItem('talibon.appearance');
                if (stored === 'light' || stored === 'dark' || stored === 'system') {
                    preference = stored;
                }
            } catch {
                // Storage may be unavailable; System remains the safe public default.
            }

            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
            const resolved = preference === 'system'
                ? (prefersDark ? 'dark' : 'light')
                : preference;

            root.classList.toggle('dark', resolved === 'dark');
            root.dataset.appearance = preference;
            root.style.colorScheme = resolved;
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
