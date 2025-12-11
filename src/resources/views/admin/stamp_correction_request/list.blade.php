{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', '申請一覧 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link
    href="{{ asset('css/stamp_correction_request/list.css') }}"
    rel="stylesheet"
/>
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="stamp-correction-request-list-container">
    <h1 class="stamp-correction-request-list-title">申請一覧</h1>

    {{-- タブ（承認待ち/承認済みの切り替え） --}}
    <div class="stamp-correction-request-list-tabs">
        {{-- 承認待ちタブ（現在選択中の場合はアクティブクラスを付与） --}}
        <a
            href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
            class="stamp-correction-request-list-tab {{
                $currentStatus === 'pending'
                    ? 'stamp-correction-request-list-tab--active'
                    : ''
            }}"
        >
            承認待ち
        </a>
        {{-- 承認済みタブ（現在選択中の場合はアクティブクラスを付与） --}}
        <a
            href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
            class="stamp-correction-request-list-tab {{
                $currentStatus === 'approved'
                    ? 'stamp-correction-request-list-tab--active'
                    : ''
            }}"
        >
            承認済み
        </a>
    </div>

    {{-- 申請一覧テーブルコンポーネント --}}
    @include('components.stamp_correction_request.list-table', [ 'requests' =>
    $requests, 'currentStatus' => $currentStatus, 'detailRoute' =>
    function($request) { return route('admin.stamp_correction_request.approve',
    ['attendance_correct_request_id' => $request->id]); }, ])
</div>
@endsection
