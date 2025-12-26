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

    {{-- 修正申請フォーム --}}
    <form method="POST" action="{{ route('attendance.correction-request', $attendance->id) }}" class="attendance-detail-form">
        @csrf
        <section class="attendance-detail-section">
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
            @include('components.attendance.detail-form-fields', [
                'userName' => $attendance->user->name,
                'dateYear' => $attendance->date->format('Y年'),
                'dateMonthDay' => $attendance->date->format('n月j日'),
                'displayClockInTime' => $displayClockInTime,
                'displayClockOutTime' => $displayClockOutTime,
                'displayNote' => $displayNote ?? '',
                'breakDetails' => $breakDetails,
                'canEdit' => $canEdit,
                'clockInTimeName' => 'clock_in_time',
                'clockOutTimeName' => 'clock_out_time',
                'breakStartTimeName' => 'break_start_times',
                'breakEndTimeName' => 'break_end_times',
                'noteName' => 'note',
                'validBreakCount' => $validBreakCount,
            ])
        </section>

        {{-- 承認待ちの修正申請がある場合の警告メッセージ --}}
        @if($hasPendingRequest)
        <div class="attendance-detail-message attendance-detail-message--error attendance-detail-message--pending">
           *承認待ちのため修正はできません。
        </div>
        @endif

        {{-- アクションボタン --}}
        @if($canEdit)
        <div class="attendance-detail-button-wrapper">
            {{-- 編集可能な場合：修正申請ボタンを表示 --}}
            <button type="submit" class="attendance-detail-edit-btn">修正</button>
        </div>
        @endif
    </form>
</div>
@endsection

