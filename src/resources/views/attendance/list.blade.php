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

    {{-- 月次ナビゲーション（前月・現在月・翌月の切り替え） --}}
    <nav class="attendance-list-month-nav">
        {{-- 前月へのリンク --}}
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
        {{-- 現在表示中の年月 --}}
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
        {{-- 翌月へのリンク --}}
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
                {{-- 勤怠データのループ表示 --}}
                @forelse($attendances as $attendance)
                <tr class="attendance-list-table__row">
                    {{-- 日付（月/日形式）と曜日 --}}
                    <td class="attendance-list-table__cell">
                        {{ $attendance->formatted_date


                        }}({{ $attendance->day_of_week }})
                    </td>
                    {{-- 出勤時刻（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->formatted_clock_in_time)
                        {{ $attendance->formatted_clock_in_time }}
                        @endif
                    </td>
                    {{-- 退勤時刻（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->formatted_clock_out_time)
                        {{ $attendance->formatted_clock_out_time }}
                        @endif
                    </td>
                    {{-- 休憩時間の合計（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->total_break_time)
                        {{ $attendance->total_break_time }}
                        @endif
                    </td>
                    {{-- 勤務時間の合計（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->total_work_time)
                        {{ $attendance->total_work_time }}
                        @endif
                    </td>
                    {{-- 詳細画面へのリンク --}}
                    <td class="attendance-list-table__cell">
                        <a
                            href="{{ route('attendance.detail', ['id' => $attendance->id]) }}"
                            class="attendance-list-table__detail-btn"
                        >
                            詳細
                        </a>
                    </td>
                </tr>
                {{-- 勤怠データが存在しない場合のメッセージ --}}
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
    {{-- 勤怠一覧テーブル終了 --}}
</div>
@endsection
