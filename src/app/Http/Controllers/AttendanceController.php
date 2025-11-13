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

    /**
     * 出勤処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockIn()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $clockInTime = $now->format('H:i:s');

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        // 既に出勤済みの場合はエラー
        if ($attendance && $attendance->clock_in_time) {
            return redirect()->route('attendance')
                ->with('error', '本日は既に出勤済みです。');
        }

        // 勤怠レコードを作成または更新
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'date' => $today,
                'clock_in_time' => $clockInTime,
                'status' => 'working',
            ]);
        } else {
            $attendance->update([
                'clock_in_time' => $clockInTime,
                'status' => 'working',
            ]);
        }

        return redirect()->route('attendance')
            ->with('success', '出勤しました。');
    }

    /**
     * 退勤処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockOut()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $clockOutTime = $now->format('H:i:s');

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        // 勤怠レコードが存在しない、または既に退勤済みの場合はエラー
        if (!$attendance) {
            return redirect()->route('attendance')
                ->with('error', '出勤記録がありません。');
        }

        if ($attendance->clock_out_time) {
            return redirect()->route('attendance')
                ->with('error', '本日は既に退勤済みです。');
        }

        // 出勤中または休憩中の状態でない場合はエラー
        if (!in_array($attendance->status, ['working', 'break'])) {
            return redirect()->route('attendance')
                ->with('error', '退勤できる状態ではありません。');
        }

        // 退勤処理
        $attendance->update([
            'clock_out_time' => $clockOutTime,
            'status' => 'finished',
        ]);

        return redirect()->route('attendance');
    }
}
