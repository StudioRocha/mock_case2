{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', 'ログイン - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/auth/auth.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="form-container form-container--login">
    <h1 class="page-title">ログイン</h1>

    <form method="POST" action="{{ route('login') }}" class="form" novalidate>
        @csrf @include('components.form.input', [ 'name' => 'email', 'label' =>
        'メールアドレス', 'type' => 'email', 'required' => true ])
        @include('components.form.input', [ 'name' => 'password', 'label' =>
        'パスワード', 'type' => 'password', 'required' => true ])

        <div class="form__submit">
            @include('components.button', [ 'type' => 'primary', 'text' =>
            'ログイン', 'buttonType' => 'submit' ])
        </div>
    </form>

    <a href="{{ route('register') }}" class="link link--login"
        >会員登録はこちら</a
    >
</div>
@endsection
