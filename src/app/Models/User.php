<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * シリアル化時に非表示にする属性
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 型キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ロールとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * 管理者かどうかを判定
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        // リレーションがロードされていない場合は自動的にロード
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }
        
        return $this->role && $this->role->isAdmin();
    }

    /**
     * 一般ユーザーかどうかを判定
     *
     * @return bool
     */
    public function isUser(): bool
    {
        // リレーションがロードされていない場合は自動的にロード
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }
        
        return $this->role && $this->role->isUser();
    }

    /**
     * 勤怠レコードとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * 修正申請レコードとのリレーション（申請者として）
     * attendance経由で取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function stampCorrectionRequests()
    {
        return $this->hasManyThrough(
            StampCorrectionRequest::class,
            Attendance::class,
            'user_id', // attendancesテーブルの外部キー
            'attendance_id', // stamp_correction_requestsテーブルの外部キー
            'id', // usersテーブルのローカルキー
            'id'  // attendancesテーブルのローカルキー
        );
    }

    /**
     * メールアドレスが認証済みかどうかを判定
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * メールアドレスを認証済みとしてマーク
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * メール認証メールを送信
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        // CreateNewUserで既に送信しているため、ここでは何もしない
        // 必要に応じて再送機能で使用
    }

}
