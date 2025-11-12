{{-- 共通レイアウト --}}
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>@yield('title', 'CT COACHTECH')</title>

        <!-- Styles -->
        <link href="{{ asset('css/sanitize.css') }}" rel="stylesheet" />
        <link href="{{ asset('css/common.css') }}" rel="stylesheet" />
        @stack('styles')
    </head>
    <body>
        @include('components.header')

        <main>@yield('content')</main>

        <!-- Scripts -->
        @stack('scripts')
    </body>
</html>
