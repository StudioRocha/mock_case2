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

    <div style="padding: 40px; text-align: center; background-color: #f5f5f5; border-radius: 8px; margin: 20px 0;">
        <h2 style="font-size: 24px; color: #333; margin-bottom: 20px;">TODO</h2>
        <p style="font-size: 18px; color: #666; line-height: 1.8;">
            PG09: 管理者勤怠詳細画面の実装予定<br>
            勤怠ID: {{ $id }}<br><br>
            機能要件: US011, FN037, FN038, FN039, FN040 を参照
        </p>
    </div>
</div>
@endsection

