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
    <div class="attendance-status">
        <span class="attendance-status__label">{{ $statusLabel }}</span>
    </div>

    <div class="attendance-date" id="attendance-date">{{ $date }}</div>

    <div class="attendance-time" id="attendance-time">{{ $time }}</div>

    @if($status === 'finished')
    <div class="attendance-message-text">お疲れ様でした。</div>
    @else
    <div class="attendance-actions">
        @if($status === 'off_duty')
        <form
            method="POST"
            action="{{ route('attendance.clock-in') }}"
            class="attendance-form"
        >
            @csrf @include('components.button', [ 'type' => 'primary', 'text' =>
            '出勤', 'buttonType' => 'submit' ])
        </form>
        @elseif($status === 'working')
        <div class="attendance-actions__group">
            <form
                method="POST"
                action="{{ route('attendance.clock-out') }}"
                class="attendance-form"
            >
                @csrf @include('components.button', [ 'type' => 'primary',
                'text' => '退勤', 'buttonType' => 'submit' ])
            </form>
            <form
                method="POST"
                action="{{ route('attendance.break-start') }}"
                class="attendance-form"
            >
                @csrf @include('components.button', [ 'type' => 'secondary',
                'text' => '休憩入', 'buttonType' => 'submit' ])
            </form>
        </div>
        @elseif($status === 'break')
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
    @endif @if(session('success'))
    <div class="attendance-message attendance-message--success">
        {{ session("success") }}
    </div>
    @endif @if(session('error'))
    <div class="attendance-message attendance-message--error">
        {{ session("error") }}
    </div>
    @endif
</div>
@endsection
