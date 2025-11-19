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
        Attendance::STATUS_OFF_DUTY => '勤務外',
        Attendance::STATUS_WORKING => '出勤中',
        Attendance::STATUS_BREAK => '休憩中',
        Attendance::STATUS_FINISHED => '退勤済',
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
            $status = Attendance::STATUS_OFF_DUTY;
            $statusLabel = self::STATUS_LABELS[Attendance::STATUS_OFF_DUTY];
        } else {
            $status = $attendance->status;
            $statusLabel = self::STATUS_LABELS[$status] ?? self::STATUS_LABELS[Attendance::STATUS_OFF_DUTY];
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
        $clockInDateTime = $now->format('Y-m-d H:i:s');

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
                'clock_in_time' => $clockInDateTime,
                'status' => Attendance::STATUS_WORKING,
            ]);
        } else {
            $attendance->update([
                'clock_in_time' => $clockInDateTime,
                'status' => Attendance::STATUS_WORKING,
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
        $clockOutDateTime = $now->format('Y-m-d H:i:s');

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
        if (!in_array($attendance->status, [Attendance::STATUS_WORKING, Attendance::STATUS_BREAK])) {
            return redirect()->route('attendance')
                ->with('error', '退勤できる状態ではありません。');
        }

        // 退勤処理（日付跨ぎの可能性を考慮して実際の日時を保存）
        $attendance->update([
            'clock_out_time' => $clockOutDateTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        return redirect()->route('attendance');
    }
}
