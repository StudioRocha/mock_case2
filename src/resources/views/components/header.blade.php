{{-- ヘッダーコンポーネント --}}
{{-- 今日の勤怠が退勤済みかどうかを判定 --}}
@php $isFinished = Auth::check() ?
\App\Models\Attendance::isFinishedToday(Auth::id()) : false; @endphp
<header class="header">
    <div class="header__inner">
        <div class="header__logo">
            <img
                src="{{ asset('images/logo.svg') }}"
                alt="CT COACHTECH"
                class="header__logo-img"
            />
        </div>
        @unless(request()->routeIs('login') || request()->routeIs('register') ||
        request()->routeIs('admin.login') || request()->routeIs('verification.notice') || request()->routeIs('verification.complete'))
        <nav class="header__nav">
            @auth @if(Auth::user()->isAdmin())
            {{-- 管理者用メニュー --}}
            <a
                href="{{ route('admin.attendance.list') }}"
                class="header__nav-link"
                >勤怠一覧</a
            >
            <a href="{{ route('admin.staff.list') }}" class="header__nav-link"
                >スタッフ一覧</a
            >
            <a
                href="{{ route('stamp_correction_request.list') }}"
                class="header__nav-link"
                >申請一覧</a
            >
            <form
                method="POST"
                action="{{ route('admin.logout') }}"
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
            @else
            {{-- 一般ユーザー用メニュー --}}
            @if($isFinished)
            <a href="{{ route('attendance.list') }}" class="header__nav-link"
                >今月の出勤一覧</a
            >
            @else
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
            @endif @if(!$isFinished)
            <a href="{{ route('attendance.list') }}" class="header__nav-link"
                >勤怠一覧</a
            >
            @endif
            <a
                href="{{ route('stamp_correction_request.list') }}"
                class="header__nav-link"
                >{{ $isFinished ? "申請一覧" : "申請" }}</a
            >
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
            @endif @endauth
        </nav>
        @endunless
    </div>
</header>
