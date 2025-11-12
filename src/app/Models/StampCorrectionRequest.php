<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_clock_in_time',
        'requested_clock_out_time',
        'requested_note',
        'status',
        'approved_at',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_clock_in_time' => 'datetime:H:i',
        'requested_clock_out_time' => 'datetime:H:i',
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
     * 申請者（ユーザー）とのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 承認者（管理者）とのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
