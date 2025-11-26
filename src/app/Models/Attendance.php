<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * 勤怠ステータスの定数
     */
    public const STATUS_OFF_DUTY = 0;
    public const STATUS_WORKING = 1;
    public const STATUS_BREAK = 2;
    public const STATUS_FINISHED = 3;

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'clock_in_time',
        'clock_out_time',
        'status',
        'note',
    ];

    /**
     * 型キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩レコードとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 修正申請レコードとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    /**
     * 指定ユーザーの今日の勤怠が退勤済みかどうかを判定
     *
     * @param int $userId
     * @return bool
     */
    public static function isFinishedToday(int $userId): bool
    {
        $today = Carbon::now()->format('Y-m-d');
        $attendance = self::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        return $attendance && $attendance->status === self::STATUS_FINISHED;
    }

    /**
     * 日をまたぐ勤怠かどうかを判定
     *
     * @return bool
     */
    public function isOvernight(): bool
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return false;
        }

        $clockIn = Carbon::parse($this->clock_in_time);
        $clockOut = Carbon::parse($this->clock_out_time);

        // 退勤時間の日付が出勤時間の日付より後、または退勤時間が出勤時間より小さい場合は日をまたぐ
        return $clockOut->format('Y-m-d') > $clockIn->format('Y-m-d') 
            || $clockOut->format('H:i') < $clockIn->format('H:i');
    }
}
