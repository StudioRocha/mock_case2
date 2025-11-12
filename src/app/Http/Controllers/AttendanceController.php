<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * ステータスラベルのマッピング
     */
    private const STATUS_LABELS = [
        'off_duty' => '勤務外',
        'working' => '出勤中',
        'break' => '休憩中',
        'finished' => '退勤済',
    ];

    /**
     * 曜日名の配列
     */
    private const WEEKDAY_NAMES = ['日', '月', '火', '水', '木', '金', '土'];

    /**
     * 勤怠登録画面を表示
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        
        // 現在の日時情報を取得
        $weekday = self::WEEKDAY_NAMES[$now->dayOfWeek];
        $date = $now->format('Y年n月j日') . '(' . $weekday . ')';
        $time = $now->format('H:i');
        
        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();
        
        // ステータスを判定
        if (!$attendance) {
            $status = 'off_duty';
            $statusLabel = self::STATUS_LABELS['off_duty'];
        } else {
            $status = $attendance->status;
            $statusLabel = self::STATUS_LABELS[$status] ?? self::STATUS_LABELS['off_duty'];
        }
        
        return view('attendance.index', [
            'date' => $date,
            'time' => $time,
            'status' => $status,
            'statusLabel' => $statusLabel,
        ]);
    }
}
