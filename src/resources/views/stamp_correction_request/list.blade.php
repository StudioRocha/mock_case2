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

    {{-- 申請一覧テーブル --}}
    <section class="stamp-correction-request-list-table-wrapper">
        <table class="stamp-correction-request-list-table">
            <thead>
                <tr>
                    <th class="stamp-correction-request-list-table__header">
                        状態
                    </th>
                    <th class="stamp-correction-request-list-table__header">
                        名前
                    </th>
                    <th class="stamp-correction-request-list-table__header">
                        対象日時
                    </th>
                    <th class="stamp-correction-request-list-table__header">
                        申請理由
                    </th>
                    <th class="stamp-correction-request-list-table__header">
                        申請日時
                    </th>
                    <th class="stamp-correction-request-list-table__header">
                        詳細
                    </th>
                </tr>
            </thead>
            <tbody>
                {{-- 修正申請データのループ表示 --}}
                @forelse($requests as $request)
                <tr class="stamp-correction-request-list-table__row">
                    {{-- 申請ステータス（承認待ち/承認済み/却下） --}}
                    <td class="stamp-correction-request-list-table__cell">
                        @if($request->status ===
                        \App\Models\StampCorrectionRequest::STATUS_PENDING)
                        承認待ち @elseif($request->status ===
                        \App\Models\StampCorrectionRequest::STATUS_APPROVED)
                        承認済み @else 却下 @endif
                    </td>
                    {{-- 申請者の名前 --}}
                    <td class="stamp-correction-request-list-table__cell">
                        {{ $request->attendance->user->name }}
                    </td>
                    {{-- 対象となる勤怠の日付（Y/m/d形式） --}}
                    <td class="stamp-correction-request-list-table__cell">
                        {{ $request->formatted_attendance_date }}
                    </td>
                    {{-- 申請理由（備考） --}}
                    <td class="stamp-correction-request-list-table__cell">
                        {{ $request->requested_note }}
                    </td>
                    {{-- 申請日時（Y/m/d形式） --}}
                    <td class="stamp-correction-request-list-table__cell">
                        {{ $request->formatted_created_at }}
                    </td>
                    {{-- 勤怠詳細画面へのリンク --}}
                    <td class="stamp-correction-request-list-table__cell">
                        <a
                            href="{{ route('attendance.detail', ['id' => $request->attendance->id]) }}"
                            class="stamp-correction-request-list-table__detail-btn"
                        >
                            詳細
                        </a>
                    </td>
                </tr>
                {{-- 申請データが存在しない場合のメッセージ --}}
                @empty
                <tr class="stamp-correction-request-list-table__row">
                    <td
                        colspan="6"
                        class="stamp-correction-request-list-table__cell stamp-correction-request-list-table__cell--empty"
                    >
                        {{-- タブの状態に応じたメッセージを表示 --}}
                        @if($currentStatus === 'pending')
                        承認待ちの申請がありません @else
                        承認済みの申請がありません @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>
    {{-- 申請一覧テーブル終了 --}}
</div>
@endsection
