<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定（モデル名とテーブル名が異なるため）
     *
     * @var string
     */
    protected $table = 'break_times';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'break_start_time',
        'break_end_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
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
}

