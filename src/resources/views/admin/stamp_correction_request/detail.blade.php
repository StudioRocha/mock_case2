{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', '勤怠詳細 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/attendance/detail.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="attendance-detail-container">
    <h1 class="attendance-detail-title">勤怠詳細</h1>

    {{-- 成功メッセージの表示（承認完了時など） --}}
    @if(session('success'))
    <div class="attendance-detail-message attendance-detail-message--success">
        {{ session("success") }}
    </div>
    @endif

    {{-- エラーメッセージの表示（承認失敗時など） --}}
    @if(session('error'))
    <div class="attendance-detail-message attendance-detail-message--error">
        {{ session("error") }}
    </div>
    @endif

    {{-- 修正申請内容の表示（読み取り専用） --}}
    <form
        method="POST"
        action="{{ route('admin.stamp_correction_request.approve', $stampCorrectionRequest->id) }}"
        class="attendance-detail-form"
    >
        @csrf
        <section class="attendance-detail-section">
            {{-- ユーザー名（読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">名前</span>
                <span
                    class="attendance-detail-value attendance-detail-name"
                    >{{ $stampCorrectionRequest->attendance->user->name }}</span
                >
            </div>
            {{-- 勤怠日付（読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">日付</span>
                <span
                    class="attendance-detail-value attendance-detail-date-year"
                    >{{ $stampCorrectionRequest->attendance->date->format('Y年') }}</span
                >
                <span
                    class="attendance-detail-date-month-day"
                    >{{ $stampCorrectionRequest->attendance->date->format('n月j日') }}</span
                >
            </div>
            {{-- 出勤・退勤時間（修正申請の内容、読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">出勤・退勤</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        value="{{ $displayClockInTime }}"
                        class="attendance-detail-time-input-field attendance-detail-time-input-field--readonly"
                        disabled
                        readonly
                    />
                </span>
                <span class="attendance-detail-time-separator">~</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        value="{{ $displayClockOutTime }}"
                        class="attendance-detail-time-input-field attendance-detail-time-input-field--readonly"
                        disabled
                        readonly
                    />
                </span>
            </div>
            {{-- 休憩時間の一覧表示（修正申請の内容、読み取り専用） --}}
            @foreach($breakDetails as $break) @if($break['should_display'])
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">
                    @if($break['break_number'] === 1) 休憩 @else 休憩{{
                        $break["break_number"]
                    }}
                    @endif
                </span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        value="{{ $break['start_time'] }}"
                        class="attendance-detail-time-input-field attendance-detail-time-input-field--readonly"
                        disabled
                        readonly
                    />
                </span>
                <span class="attendance-detail-time-separator">~</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        value="{{ $break['end_time'] }}"
                        class="attendance-detail-time-input-field attendance-detail-time-input-field--readonly"
                        disabled
                        readonly
                    />
                </span>
            </div>
            @endif @endforeach
            {{-- 備考欄（修正申請の内容、読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">備考</span>
                <textarea
                    class="attendance-detail-note-field attendance-detail-note-field--readonly"
                    disabled
                    readonly
                    >{{ $displayNote }}</textarea
                >
            </div>
        </section>

        {{-- アクションボタン --}}
        <div class="attendance-detail-button-wrapper">
            @if($canApprove)
            {{-- 承認可能な場合：承認ボタンを表示 --}}
            <button type="submit" class="attendance-detail-edit-btn">
                承認
            </button>
            @else
            {{-- 承認済みの場合：承認済みボタンを表示（無効化） --}}
            <button
                type="button"
                class="attendance-detail-edit-btn attendance-detail-edit-btn--approved"
                disabled
            >
                承認済み
            </button>
            @endif
        </div>
    </form>
</div>
@endsection
