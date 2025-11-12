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
     * The attributes that are mass assignable.
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
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
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
