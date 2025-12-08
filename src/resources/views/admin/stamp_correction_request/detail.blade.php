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

    {{-- メッセージ表示コンポーネント --}}
    @include('components.attendance.detail-messages')

    {{-- 修正申請内容の表示（読み取り専用） --}}
    <form
        method="POST"
        action="{{ route('admin.stamp_correction_request.approve', $stampCorrectionRequest->id) }}"
        class="attendance-detail-form"
    >
        @csrf
        <section class="attendance-detail-section">
            @include('components.attendance.detail-form-fields', [ 'userName' =>
            $stampCorrectionRequest->attendance->user->name, 'dateYear' =>
            $stampCorrectionRequest->attendance->date->format('Y年'),
            'dateMonthDay' =>
            $stampCorrectionRequest->attendance->date->format('n月j日'),
            'displayClockInTime' => $displayClockInTime, 'displayClockOutTime'
            => $displayClockOutTime, 'displayNote' => $displayNote,
            'breakDetails' => $breakDetails, 'canEdit' => false, 'isReadonly' =>
            true, ])
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
