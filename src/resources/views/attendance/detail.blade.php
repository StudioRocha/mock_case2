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

    @if(session('success'))
    <div class="attendance-detail-message attendance-detail-message--success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="attendance-detail-message attendance-detail-message--error">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="attendance-detail-message attendance-detail-message--error">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- 基本情報 --}}
    <form method="POST" action="{{ route('attendance.correction-request', $attendance->id) }}" class="attendance-detail-form">
        @csrf
        <section class="attendance-detail-section">
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">名前</span>
                <span class="attendance-detail-value">{{ $attendance->user->name }}</span>
            </div>
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">日付</span>
                <span class="attendance-detail-value">{!! $formattedDate !!}</span>
            </div>
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">出勤・退勤</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        name="clock_in_time"
                        value="{{ $attendance->clock_in_time ? \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') : '' }}"
                        class="attendance-detail-time-input-field"
                        {{ !$canEdit ? 'disabled' : '' }}
                    />
                </span>
                <span class="attendance-detail-time-separator">~</span>
                <span class="attendance-detail-time-col">
                    <input
                        type="time"
                        name="clock_out_time"
                        value="{{ $attendance->clock_out_time ? \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') : '' }}"
                        class="attendance-detail-time-input-field"
                        {{ !$canEdit ? 'disabled' : '' }}
                    />
                </span>
            </div>
            @foreach($breakDetails as $index => $break)
                @php
                    $startTime = $break['start_time'] ?? '';
                    $endTime = $break['end_time'] ?? '';
                    // 開始時間と終了時間の両方が存在する場合のみ表示
                    $hasValidBreak = !empty($startTime) && !empty($endTime) && $startTime !== '-' && $endTime !== '-';
                @endphp
                @if($hasValidBreak)
                <div class="attendance-detail-item">
                    <span class="attendance-detail-label">
                        @if($index === 0)
                            休憩
                        @else
                            休憩{{ $index + 1 }}
                        @endif
                    </span>
                    <span class="attendance-detail-time-col">
                        <input
                            type="time"
                            name="break_start_times[]"
                            value="{{ $startTime }}"
                            class="attendance-detail-time-input-field"
                            {{ !$canEdit ? 'disabled' : '' }}
                        />
                    </span>
                    <span class="attendance-detail-time-separator">~</span>
                    <span class="attendance-detail-time-col">
                        <input
                            type="time"
                            name="break_end_times[]"
                            value="{{ $endTime }}"
                            class="attendance-detail-time-input-field"
                            {{ !$canEdit ? 'disabled' : '' }}
                        />
                    </span>
                </div>
                @endif
            @endforeach
            <div class="attendance-detail-item">
                <span class="attendance-detail-label">備考</span>
                <textarea
                    name="note"
                    class="attendance-detail-note-field"
                    {{ !$canEdit ? 'disabled' : '' }}
                >{{ $displayNote ?? '' }}</textarea>
            </div>
        </section>

        @if($hasPendingRequest)
        <div class="attendance-detail-message attendance-detail-message--error attendance-detail-message--pending">
            承認待ちのため修正はできません。
        </div>
        @endif

        {{-- 修正ボタン --}}
        <div class="attendance-detail-button-wrapper">
            @if($canEdit)
            <button type="submit" class="attendance-detail-edit-btn">修正</button>
            @else
            <a href="{{ route('attendance.list') }}" class="attendance-detail-back-btn">一覧に戻る</a>
            @endif
        </div>
    </form>
</div>
@endsection

