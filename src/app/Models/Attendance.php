<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime:H:i',
        'clock_out_time' => 'datetime:H:i',
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
}
