{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', 'メール認証完了 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/auth/auth.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="form-container form-container--verify-email">
    <div class="verify-email-message">
        <p class="verify-email-message__text">
            メールアドレスの認証が完了しました。
        </p>
    </div>
</div>
@endsection

