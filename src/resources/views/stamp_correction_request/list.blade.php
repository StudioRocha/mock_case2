{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', '申請一覧 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/stamp_correction_request/list.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="stamp-correction-request-list-container">
    <h1 class="stamp-correction-request-list-title">申請一覧</h1>

    {{-- タブ --}}
    <div class="stamp-correction-request-list-tabs">
        <a
            href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
            class="stamp-correction-request-list-tab {{ $currentStatus === 'pending' ? 'stamp-correction-request-list-tab--active' : '' }}"
        >
            承認待ち
        </a>
        <a
            href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
            class="stamp-correction-request-list-tab {{ $currentStatus === 'approved' ? 'stamp-correction-request-list-tab--active' : '' }}"
        >
            承認済み
        </a>
    </div>

    {{-- 申請一覧テーブル --}}
    <section class="stamp-correction-request-list-table-wrapper">
        <table class="stamp-correction-request-list-table">
            <thead>
                <tr>
                    <th class="stamp-correction-request-list-table__header">状態</th>
                    <th class="stamp-correction-request-list-table__header">名前</th>
                    <th class="stamp-correction-request-list-table__header">対象日時</th>
                    <th class="stamp-correction-request-list-table__header">申請理由</th>
                    <th class="stamp-correction-request-list-table__header">申請日時</th>
                    <th class="stamp-correction-request-list-table__header">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                <tr class="stamp-correction-request-list-table__row">
                    <td class="stamp-correction-request-list-table__cell">
                        @if($request->status === \App\Models\StampCorrectionRequest::STATUS_PENDING)
                            承認待ち
                        @elseif($request->status === \App\Models\StampCorrectionRequest::STATUS_APPROVED)
                            承認済み
                        @else
                            却下
                        @endif
                    </td>
                    <td class="stamp-correction-request-list-table__cell">
                        {{ $request->attendance->user->name }}
                    </td>
                    <td class="stamp-correction-request-list-table__cell">
                        {{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}
                    </td>
                    <td class="stamp-correction-request-list-table__cell">
                        {{ $request->requested_note }}
                    </td>
                    <td class="stamp-correction-request-list-table__cell">
                        {{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}
                    </td>
                    <td class="stamp-correction-request-list-table__cell">
                        <a
                            href="{{ route('attendance.detail', ['id' => $request->attendance->id]) }}"
                            class="stamp-correction-request-list-table__detail-btn"
                        >
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr class="stamp-correction-request-list-table__row">
                    <td
                        colspan="6"
                        class="stamp-correction-request-list-table__cell stamp-correction-request-list-table__cell--empty"
                    >
                        @if($currentStatus === 'pending')
                            承認待ちの申請がありません
                        @else
                            承認済みの申請がありません
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection

