{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', 'メールアドレスの認証 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/auth/auth.css') }}" rel="stylesheet" />
@endpush

{{-- メインコンテンツ開始 --}}
@section('content')
<div class="form-container form-container--verify-email">
    @if (Auth::user() && !Auth::user()->hasVerifiedEmail())
    <div class="verify-email-message">
        <p class="verify-email-message__text">
            登録していただいたメールアドレスに認証メールを送付しました。
        </p>
        <p class="verify-email-message__text">メール認証を完了してください。</p>
    </div>

    <div class="verify-email-actions">
        {{-- MailHog受信箱を開くボタン --}}
        <div class="form__submit">
            <a href="http://localhost:8025/" target="_blank" style="display: inline-block; text-decoration: none;">
                @include('components.button', [ 'type' => 'secondary', 'text' =>
                '認証はこちらから', 'buttonType' => 'button' ])
            </a>
        </div>

        {{-- 認証メール再送リンク --}}
        <form
            method="POST"
            action="{{ route('verification.send') }}"
            class="verify-email-resend"
        >
            @csrf
            <button type="submit" class="verify-email-resend__link">
                認証メールを再送する
            </button>
        </form>
    </div>
    @else
    <div class="verify-email-message">
        <p class="verify-email-message__text">
            メールアドレスは既に認証済みです。
        </p>
    </div>
    <div class="form__submit">
        <a href="{{ route('attendance') }}" style="display: inline-block">
            @include('components.button', [ 'type' => 'primary', 'text' =>
            '勤怠登録画面へ', 'buttonType' => 'button' ])
        </a>
    </div>
    @endif
</div>
@endsection
