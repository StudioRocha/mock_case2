<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * ユーザーロールの定数
     */
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'user_id');
    }

    /**
     * 承認した修正申請レコードとのリレーション（承認者として）
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvedStampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'approved_by');
    }
}
