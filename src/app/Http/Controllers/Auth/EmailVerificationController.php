<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * メール認証を実行
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  string  $hash
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // ハッシュが一致しない場合
        if (sha1($user->email) !== $hash) {
            return redirect()->route('verification.notice')
                ->with('error', '認証リンクが無効です。');
        }

        // 開発環境では署名チェックをスキップ（開発者用ボタン用）
        $isDevelopment = app()->environment('local', 'testing');
        
        // 署名が無効または期限切れの場合（開発環境以外）
        if (!$isDevelopment && !URL::hasValidSignature($request)) {
            return redirect()->route('verification.notice')
                ->with('error', '認証リンクの有効期限が切れています。認証メールを再送してください。');
        }

        // 既に認証済みの場合
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('attendance')
                ->with('info', '既にメールアドレスは認証済みです。');
        }

        // メール認証を完了
        $user->markEmailAsVerified();

        return redirect()->route('attendance')
            ->with('success', 'メールアドレスの認証が完了しました。');
    }

    /**
     * 開発者用：直接認証を実行（署名チェックなし）
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyDev(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'ログインが必要です。');
        }

        // 既に認証済みの場合
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('attendance')
                ->with('info', '既にメールアドレスは認証済みです。');
        }

        // メール認証を完了
        $user->markEmailAsVerified();

        return redirect()->route('attendance')
            ->with('success', 'メールアドレスの認証が完了しました。');
    }

    /**
     * 認証メール再送
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'ログインが必要です。');
        }

        // 既に認証済みの場合
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('attendance')
                ->with('info', '既にメールアドレスは認証済みです。');
        }

        // 認証メールを再送
        Mail::to($user->email)->send(new VerifyEmail($user));

        return redirect()->route('verification.notice')
            ->with('success', '認証メールを再送しました。メールをご確認ください。');
    }
}

