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

    {{-- 成功メッセージの表示（修正完了時など） --}}
    @if(session('success'))
    <div class="attendance-detail-message attendance-detail-message--success">
        {{ session('success') }}
    </div>
    @endif

    {{-- エラーメッセージの表示（修正失敗時など） --}}
    @if(session('error'))
    <div class="attendance-detail-message attendance-detail-message--error">
        {{ session('error') }}
    </div>
    @endif

    {{-- バリデーションエラーメッセージの表示 --}}
    @if($errors->any())
    <div class="attendance-detail-message attendance-detail-message--error">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- 修正フォーム（管理者は直接修正） --}}
    <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}" class="attendance-detail-form">
        @csrf
        @method('PUT')
        <section class="attendance-detail-section">
            {{-- ユーザー名（読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">名前</span>
                <span class="attendance-detail-value attendance-detail-name">{{ $attendance->user->name }}</span>
            </div>
            {{-- 勤怠日付（読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">日付</span>
                <span class="attendance-detail-value attendance-detail-date-year">{{ $attendance->date->format('Y年') }}</span>
                <span class="attendance-detail-date-month-day">{{ $attendance->date->format('n月j日') }}</span>
            </div>
            {{-- 出勤・退勤時間（編集可能/読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">出勤・退勤</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        name="clock_in_time"
                        value="{{ $displayClockInTime }}"
                        class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
                        {{ !$canEdit ? 'disabled' : '' }}
                    />
                </span>
                <span class="attendance-detail-time-separator">~</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        name="clock_out_time"
                        value="{{ $displayClockOutTime }}"
                        class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
                        {{ !$canEdit ? 'disabled' : '' }}
                    />
                </span>
            </div>
            {{-- 休憩時間の一覧表示（複数の休憩に対応） --}}
            @php
                // 有効な休憩の数をカウント（最後の空白休憩を除く）
                $validBreakCount = 0;
                foreach ($breakDetails as $break) {
                    $startTime = $break['start_time'] ?? '';
                    $endTime = $break['end_time'] ?? '';
                    if (!empty($startTime) && !empty($endTime) && $startTime !== '-' && $endTime !== '-') {
                        $validBreakCount++;
                    }
                }
            @endphp
            @foreach($breakDetails as $index => $break)
                @php
                    $startTime = $break['start_time'] ?? '';
                    $endTime = $break['end_time'] ?? '';
                    // 有効な休憩かどうか（開始時間と終了時間の両方が存在する場合）
                    $hasValidBreak = !empty($startTime) && !empty($endTime) && $startTime !== '-' && $endTime !== '-';
                    // 最後の要素（修正用の空白休憩）かどうかを判定
                    $isLastBreak = $index === count($breakDetails) - 1;
                    // 有効な休憩、または最後の空白休憩の場合は表示
                    $shouldDisplay = $hasValidBreak || $isLastBreak;
                    // 表示する休憩の番号を決定
                    $breakNumber = $hasValidBreak ? ($index + 1) : ($validBreakCount + 1);
                @endphp
                @if($shouldDisplay)
                <div class="attendance-detail-item">
                    <span class="attendance-detail-label">
                        @if($breakNumber === 1)
                            休憩
                        @else
                            休憩{{ $breakNumber }}
                        @endif
                    </span>
                    <span class="attendance-detail-time-col">
                        <input
                            type="time"
                            name="break_start_times[]"
                            value="{{ $startTime }}"
                            class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
                            {{ !$canEdit ? 'disabled' : '' }}
                        />
                    </span>
                    <span class="attendance-detail-time-separator">~</span>
                    <span class="attendance-detail-time-col">
                        <input
                            type="time"
                            name="break_end_times[]"
                            value="{{ $endTime }}"
                            class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
                            {{ !$canEdit ? 'disabled' : '' }}
                        />
                    </span>
                </div>
                @endif
            @endforeach
            {{-- 備考欄（編集可能/読み取り専用） --}}
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">備考</span>
                <textarea
                    name="note"
                    class="attendance-detail-note-field {{ !$canEdit ? 'attendance-detail-note-field--readonly' : '' }}"
                    {{ !$canEdit ? 'disabled' : '' }}
                >{{ $displayNote ?? '' }}</textarea>
            </div>
        </section>

        {{-- 承認待ちの修正申請がある場合の警告メッセージ --}}
        @if($hasPendingRequest)
        <div class="attendance-detail-message attendance-detail-message--error attendance-detail-message--pending">
            承認待ちのため修正はできません。
        </div>
        @endif

        {{-- アクションボタン --}}
        <div class="attendance-detail-button-wrapper">
            @if($canEdit)
            {{-- 編集可能な場合：修正ボタンを表示 --}}
            <button type="submit" class="attendance-detail-edit-btn">修正</button>
            @else
            {{-- 編集不可の場合：一覧に戻るボタンを表示 --}}
            <a href="{{ route('admin.attendance.list') }}" class="attendance-detail-back-btn">一覧に戻る</a>
            @endif
        </div>
    </form>
</div>
@endsection
