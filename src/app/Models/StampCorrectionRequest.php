<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'requested_clock_in_time',
        'requested_clock_out_time',
        'requested_note',
        'status',
        'approved_at',
    ];

    /**
     * 型キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_clock_in_time' => 'datetime',
        'requested_clock_out_time' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * 勤怠レコードとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 申請者（ユーザー）を取得（attendance経由）
     * 
     * @return \App\Models\User|null
     */
    public function getUserAttribute()
    {
        return $this->attendance->user ?? null;
    }

    /**
     * 休憩修正レコードとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function breakCorrections()
    {
        return $this->hasMany(BreakCorrection::class);
    }
}
