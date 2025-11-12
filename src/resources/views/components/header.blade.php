{{-- ヘッダーコンポーネント --}}
<header class="header">
    <div class="header__inner">
        <div class="header__logo">
            <img
                src="{{ asset('images/logo.svg') }}"
                alt="CT COACHTECH"
                class="header__logo-img"
            />
        </div>
        @unless(request()->routeIs('login') || request()->routeIs('register'))
        <nav class="header__nav">
            <form
                method="GET"
                action="{{ route('attendance') }}"
                class="header__nav-form"
            >
                <button
                    type="submit"
                    class="header__nav-link header__nav-link--button"
                >
                    勤怠
                </button>
            </form>
            <a href="#" class="header__nav-link">勤怠一覧</a>
            <a href="#" class="header__nav-link">申請</a>
            <form
                method="POST"
                action="{{ route('logout') }}"
                class="header__nav-form"
            >
                @csrf
                <button
                    type="submit"
                    class="header__nav-link header__nav-link--button"
                >
                    ログアウト
                </button>
            </form>
        </nav>
        @endunless
    </div>
</header>
