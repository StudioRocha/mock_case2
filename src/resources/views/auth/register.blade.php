@extends('layouts.app') @section('title', '会員登録 - CT COACHTECH')
@push('styles')
<link href="{{ asset('css/auth/register.css') }}" rel="stylesheet" />
@endpush @section('content')
<div class="form-container">
    <h1 class="page-title">会員登録</h1>

    <form
        method="POST"
        action="{{ route('register') }}"
        class="form"
        novalidate
    >
        @csrf @include('components.form.input', [ 'name' => 'name', 'label' =>
        '名前', 'type' => 'text', 'required' => true ])
        @include('components.form.input', [ 'name' => 'email', 'label' =>
        'メールアドレス', 'type' => 'email', 'required' => true ])
        @include('components.form.input', [ 'name' => 'password', 'label' =>
        'パスワード', 'type' => 'password', 'required' => true ])
        @include('components.form.input', [ 'name' => 'password_confirmation',
        'label' => 'パスワード確認', 'type' => 'password', 'required' => true ])

        <div class="form__submit">
            @include('components.button', [ 'type' => 'primary', 'text' =>
            '登録する', 'buttonType' => 'submit' ])
        </div>
    </form>

    <a href="{{ route('login') }}" class="link link--login">ログインはこちら</a>
</div>
@endsection
