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
                @if($attendances->count() > 0) @foreach($attendances as
                $attendance)
                <tr class="attendance-list-table__row">
                    {{-- 日付（月/日形式）と曜日 --}}
                    <td class="attendance-list-table__cell">
                        <span class="attendance-list-table__cell-text">
                            {{ $attendance->formatted_date


                            }}({{ $attendance->day_of_week }})
                        </span>
                    </td>
                    {{-- 出勤時刻（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->formatted_clock_in_time)
                        <span class="attendance-list-table__cell-text">
                            {{ $attendance->formatted_clock_in_time }}
                        </span>
                        @else
                        <span class="attendance-list-table__cell-text"
                            >&nbsp;</span
                        >
                        @endif
                    </td>
                    {{-- 退勤時刻（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->formatted_clock_out_time)
                        <span class="attendance-list-table__cell-text">
                            {{ $attendance->formatted_clock_out_time }}
                        </span>
                        @else
                        <span class="attendance-list-table__cell-text"
                            >&nbsp;</span
                        >
                        @endif
                    </td>
                    {{-- 休憩時間の合計（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->status ===
                        \App\Models\Attendance::STATUS_FINISHED &&
                        $attendance->total_break_time)
                        <span class="attendance-list-table__cell-text">
                            {{ $attendance->total_break_time }}
                        </span>
                        @else
                        <span class="attendance-list-table__cell-text"
                            >&nbsp;</span
                        >
                        @endif
                    </td>
                    {{-- 勤務時間の合計（H:i形式、存在する場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->total_work_time)
                        <span class="attendance-list-table__cell-text">
                            {{ $attendance->total_work_time }}
                        </span>
                        @else
                        <span class="attendance-list-table__cell-text"
                            >&nbsp;</span
                        >
                        @endif
                    </td>
                    {{-- 詳細画面へのリンク（勤怠データがある場合のみ表示） --}}
                    <td class="attendance-list-table__cell">
                        @if($attendance->id ?? null)
                        <a
                            href="{{ route('attendance.detail', ['id' => $attendance->id]) }}"
                            class="attendance-list-table__detail-btn"
                        >
                            詳細
                        </a>
                        @else
                        <span class="attendance-list-table__cell-text"
                            >&nbsp;</span
                        >
                        @endif
                    </td>
                </tr>
                @endforeach @else
                {{-- 勤怠データが存在しない場合のメッセージ --}}
                <tr class="attendance-list-table__row">
                    <td
                        colspan="6"
                        class="attendance-list-table__cell attendance-list-table__cell--empty"
                    >
                        勤怠記録がありません
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </section>
    {{-- 勤怠一覧テーブル終了 --}}
</div>
@endsection
