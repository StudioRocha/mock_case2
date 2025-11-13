{{-- ヘッダーコンポーネント --}}
@php $isFinished = false; if (Auth::check()) { $today =
\Carbon\Carbon::now()->format('Y-m-d'); $attendance =
\App\Models\Attendance::where('user_id', Auth::id()) ->where('date', $today)
->first(); $isFinished = $attendance && $attendance->status === 'finished'; }
@endphp
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
            @if($isFinished)
            <a href="#" class="header__nav-link">今月の出勤一覧</a>
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
            @endif
            <a href="#" class="header__nav-link">勤怠一覧</a>
            @unless($isFinished)
            <a href="#" class="header__nav-link">申請</a>
            @endunless
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
