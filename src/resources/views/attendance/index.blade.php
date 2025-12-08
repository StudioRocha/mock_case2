{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', '勤怠登録 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/attendance/index.css') }}" rel="stylesheet" />
@endpush

{{-- JavaScriptを追加 --}}
@push('scripts')
<script src="{{ asset('js/attendance/attendance-time.js') }}"></script>
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="attendance-container">
    <h1 class="attendance-title"></h1>

    {{-- 現在の勤怠ステータス表示（勤務外、出勤中、休憩中、退勤済） --}}
    <div class="attendance-status">
        <span
            class="attendance-status__label {{ $status === \App\Models\Attendance::STATUS_FINISHED ? 'attendance-status__label--finished' : '' }}"
            >{{ $statusLabel }}</span
        >
    </div>

    {{-- 現在の日付表示（JavaScriptで更新される） --}}
    <div class="attendance-date" id="attendance-date">{{ $date }}</div>

    {{-- 現在の時刻表示（JavaScriptで更新される） --}}
    <div class="attendance-time" id="attendance-time">{{ $time }}</div>

    {{-- 退勤済みの場合：メッセージを表示 --}}
    @if($status === \App\Models\Attendance::STATUS_FINISHED)
    <p class="attendance-message-text">お疲れ様でした。</p>
    @else
    {{-- アクションボタン表示エリア --}}
    <div class="attendance-actions">
        {{-- 勤務外の場合：出勤ボタンを表示 --}}
        @if($status === \App\Models\Attendance::STATUS_OFF_DUTY)
        <form
            method="POST"
            action="{{ route('attendance.clock-in') }}"
            class="attendance-form"
        >
            @csrf @include('components.button', [ 'type' => 'primary', 'text' =>
            '出勤', 'buttonType' => 'submit' ])
        </form>
        {{-- 出勤中の場合：退勤ボタンと休憩入ボタンを表示 --}}
        @elseif($status === \App\Models\Attendance::STATUS_WORKING)
        <div class="attendance-actions__group">
            <form
                method="POST"
                action="{{ route('attendance.clock-out') }}"
                class="attendance-form"
            >
                @csrf @include('components.button', [ 'type' => 'primary',
                'text' => '退勤', 'buttonType' => 'submit' ])
            </form>
            {{-- 休憩開始ボタン --}}
            <form
                method="POST"
                action="{{ route('attendance.break-start') }}"
                class="attendance-form"
            >
                @csrf @include('components.button', [ 'type' => 'secondary',
                'text' => '休憩入', 'buttonType' => 'submit' ])
            </form>
        </div>
        {{-- 休憩中の場合：休憩戻ボタンを表示 --}}
        @elseif($status === \App\Models\Attendance::STATUS_BREAK)
        <form
            method="POST"
            action="{{ route('attendance.break-end') }}"
            class="attendance-form"
        >
            @csrf @include('components.button', [ 'type' => 'primary', 'text' =>
            '休憩戻', 'buttonType' => 'submit' ])
        </form>
        @endif
    </div>
    @endif

    {{-- エラーメッセージの表示（出勤済み、退勤済みなどのエラー時） --}}
    @if(session('error'))
    <div class="attendance-message attendance-message--error">
        {{ session("error") }}
    </div>
    @endif
</div>
@endsection
