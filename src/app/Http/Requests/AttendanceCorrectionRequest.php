<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Http\Requests\Concerns\ValidatesAttendanceData;

class AttendanceCorrectionRequest extends FormRequest
{   
    // トレイト参照
    use ValidatesAttendanceData;

    /**
     * リクエストの認証を許可するかどうか
     * 自分の勤怠のみ申請可能
     *
     * @return bool
     */
    public function authorize()
    {
        // 勤怠レコードを取得（ルートパラメータから）
        $attendanceId = $this->route('id');
        $attendance = $attendanceId ? Attendance::find($attendanceId) : null;

        // 勤怠レコードが存在し、かつ自分の勤怠である場合のみ許可
        return $attendance && $attendance->user_id === Auth::id();
    }
}

