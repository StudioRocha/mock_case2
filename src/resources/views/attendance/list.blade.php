{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', '勤怠一覧 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/attendance/list.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="attendance-list-container">
    <h1 class="attendance-list-title">勤怠一覧</h1>

    {{-- 月次ナビゲーション --}}
    <nav class="attendance-list-month-nav">
        <a
            href="{{ route('attendance.list', ['year' => $prevYear, 'month' => $prevMonth]) }}"
            class="attendance-list-month-nav__link attendance-list-month-nav__link--prev"
        >
            <img
                src="{{ asset('images/pagenation_vector.png') }}"
                alt="前月"
                class="attendance-list-month-nav__arrow"
            />
            前月
        </a>
        <div class="attendance-list-month-nav__center">
            <img
                src="{{ asset('images/calender.png') }}"
                alt="カレンダー"
                class="attendance-list-month-nav__icon"
            />
            <span class="attendance-list-month-nav__current"
                >{{ $currentYear }}/{{
                    str_pad($currentMonth, 2, "0", STR_PAD_LEFT)
                }}</span
            >
        </div>
        <a
            href="{{ route('attendance.list', ['year' => $nextYear, 'month' => $nextMonth]) }}"
            class="attendance-list-month-nav__link attendance-list-month-nav__link--next"
        >
            翌月
            <img
                src="{{ asset('images/pagenation_vector.png') }}"
                alt="翌月"
                class="attendance-list-month-nav__arrow attendance-list-month-nav__arrow--reverse"
            />
        </a>
    </nav>

    {{-- 勤怠一覧テーブル --}}
    <section class="attendance-list-table-wrapper">
        <table class="attendance-list-table">
            <thead>
                <tr>
                    <th class="attendance-list-table__header">日付</th>
                    <th class="attendance-list-table__header">出勤</th>
                    <th class="attendance-list-table__header">退勤</th>
                    <th class="attendance-list-table__header">休憩</th>
                    <th class="attendance-list-table__header">合計</th>
                    <th class="attendance-list-table__header">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                <tr class="attendance-list-table__row">
                    <td class="attendance-list-table__cell">
                        {{ \Carbon\Carbon::parse($attendance->date)->format('m/d')




                        }}({{ ['日', '月', '火', '水', '木', '金', '土'][\Carbon\Carbon::parse($attendance->date)->dayOfWeek]




                        }})
                    </td>
                    <td class="attendance-list-table__cell">
                        @if($attendance->clock_in_time)
                        {{ \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') }}
                        @endif
                    </td>
                    <td class="attendance-list-table__cell">
                        @if($attendance->clock_out_time)
                        {{ \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') }}
                        @endif
                    </td>
                    <td class="attendance-list-table__cell">
                        @if($attendance->total_break_time)
                        {{ $attendance->total_break_time }}
                        @endif
                    </td>
                    <td class="attendance-list-table__cell">
                        @if($attendance->total_work_time)
                        {{ $attendance->total_work_time }}
                        @endif
                    </td>
                    <td class="attendance-list-table__cell">
                        <a
                            href="{{ route('attendance.detail', ['id' => $attendance->id]) }}"
                            class="attendance-list-table__detail-btn"
                        >
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr class="attendance-list-table__row">
                    <td
                        colspan="6"
                        class="attendance-list-table__cell attendance-list-table__cell--empty"
                    >
                        勤怠記録がありません
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
