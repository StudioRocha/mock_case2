{{-- 共通レイアウトを継承 --}}
@extends('layouts.app')
{{-- ページタイトルを設定 --}}
@section('title', 'メールアドレスの認証 - CT COACHTECH')

{{-- スタイルシートを追加 --}}
@push('styles')
<link href="{{ asset('css/auth/auth.css') }}" rel="stylesheet" />
@endpush

{{-- JavaScriptを追加 --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 既に認証済みの場合はポーリング不要
    @if(Auth::user() && Auth::user()->hasVerifiedEmail())
        return;
    @endif

    let pollInterval;
    let pollCount = 0;
    const MAX_POLL_COUNT = 300; // 最大5分間（1秒間隔 × 300回）
    const POLL_INTERVAL = 1000; // 1秒間隔

    // ポーリング開始
    function startPolling() {
        pollInterval = setInterval(function() {
            pollCount++;
            
            // 最大ポーリング回数に達したら停止
            if (pollCount >= MAX_POLL_COUNT) {
                clearInterval(pollInterval);
                console.log('ポーリングが最大回数に達しました。');
                return;
            }

            // APIを呼び出して認証状態をチェック
            fetch('{{ route("verification.check-status") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.verified) {
                    // 認証が完了したらポーリングを停止
                    clearInterval(pollInterval);
                    
                    // 現在のタブをアクティブにしてからリダイレクト
                    window.focus();
                    
                    // 少し遅延を入れてからリダイレクト（タブがアクティブになるのを待つ）
                    setTimeout(function() {
                        window.location.href = '{{ route("attendance") }}';
                    }, 100);
                }
            })
            .catch(error => {
                console.error('認証状態のチェック中にエラーが発生しました:', error);
            });
        }, POLL_INTERVAL);
    }

    // ポーリング開始
    startPolling();

    // ページを離れる際にポーリングを停止
    window.addEventListener('beforeunload', function() {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });
});
</script>
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
