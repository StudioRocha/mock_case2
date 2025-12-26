{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', 'スタッフ一覧 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/attendance/list.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="attendance-list-container">
    <h1 class="attendance-list-title">スタッフ一覧</h1>

    {{-- スタッフ一覧テーブル --}}
    <section class="attendance-list-table-wrapper">
        <table class="attendance-list-table">
            <thead>
                <tr>
                    <th class="attendance-list-table__header">名前</th>
                    <th class="attendance-list-table__header">
                        メールアドレス
                    </th>
                    <th class="attendance-list-table__header">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                {{-- スタッフデータのループ表示 --}}
                @if($staffs->count() > 0) @foreach($staffs as $staff)
                <tr class="attendance-list-table__row">
                    {{-- 名前 --}}
                    <td class="attendance-list-table__cell">
                        <span
                            class="attendance-list-table__cell-text"
                            >{{ $staff->name ?? '-' }}</span
                        >
                    </td>
                    {{-- メールアドレス --}}
                    <td class="attendance-list-table__cell">
                        <span
                            class="attendance-list-table__cell-text"
                            >{{ $staff->email ?? '-' }}</span
                        >
                    </td>
                    {{-- 月次勤怠詳細画面へのリンク（FN042: 月次勤怠遷移機能） --}}
                    <td class="attendance-list-table__cell">
                        @if($staff->id ?? null)
                        <a
                            href="{{ route('admin.attendance.monthly', ['id' => $staff->id]) }}"
                            class="attendance-list-table__detail-btn"
                        >
                            詳細
                        </a>
                        @else
                        <span class="attendance-list-table__cell-text"
                            >&nbsp;</span
                        >
                        @endif
                    </td>
                </tr>
                @endforeach @else
                {{-- スタッフデータが存在しない場合のメッセージ --}}
                <tr class="attendance-list-table__row">
                    <td
                        colspan="3"
                        class="attendance-list-table__cell attendance-list-table__cell--empty"
                    >
                        スタッフが登録されていません
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </section>
    {{-- スタッフ一覧テーブル終了 --}}
</div>
@endsection
