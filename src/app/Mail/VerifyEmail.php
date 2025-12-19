<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->verificationUrl = $this->verificationUrl($user);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address', 'noreply@example.com'), config('mail.from.name', 'CT COACHTECH'))
                    ->subject('メールアドレスの認証をお願いします')
                    ->view('emails.verify-email')
                    ->with([
                        'user' => $this->user,
                        'verificationUrl' => $this->verificationUrl,
                    ]);
    }

    /**
     * メール認証用のURLを生成
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    protected function verificationUrl($user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24), // 24時間有効
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
    }
}

