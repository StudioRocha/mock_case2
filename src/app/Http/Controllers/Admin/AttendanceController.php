<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 曜日名の配列
     */
    private const WEEKDAY_NAMES = ['日', '月', '火', '水', '木', '金', '土'];

    /**
     * 日付・時間フォーマット定数
     */
    private const DATE_FORMAT = 'Y-m-d';
    private const TIME_FORMAT = 'H:i';
    private const DISPLAY_DATE_FORMAT = 'Y年n月j日';

    /**
     * 休憩時間の合計（分）を計算
     *
     * @param Attendance $attendance
     * @return int
     */
    private function calculateBreakTime($attendance)
    {
        $totalBreakMinutes = 0;
        foreach ($attendance->breaks as $break) {
            if ($break->break_start_time && $break->break_end_time) {
                $totalBreakMinutes += $break->break_end_time->diffInMinutes($break->break_start_time);
            }
        }
        return $totalBreakMinutes;
    }

    /**
     * 勤務時間の合計（分）を計算
     *
     * @param Attendance $attendance
     * @param int $totalBreakMinutes 休憩時間の合計（分）
     * @return int 労働時間（分）
     */
    private function calculateWorkTime($attendance, $totalBreakMinutes)
    {
        if (!$attendance->clock_in_time || !$attendance->clock_out_time) {
            return 0;
        }

        $totalMinutes = $attendance->clock_out_time->diffInMinutes($attendance->clock_in_time);
        $workMinutes = $totalMinutes - $totalBreakMinutes;

        return max(0, $workMinutes);
    }

    /**
     * 分をH:i形式の文字列に変換
     *
     * @param int $minutes
     * @return string|null
     */
    private function formatMinutesToTime($minutes)
    {
        if ($minutes <= 0) {
            return null;
        }
        return floor($minutes / 60) . ':' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 管理者勤怠一覧画面（日次）を表示
     *
     * @param int|null $year
     * @param int|null $month
     * @param int|null $day
     * @return \Illuminate\View\View
     */
    public function list($year = null, $month = null, $day = null)
    {
        $now = Carbon::now();

        // 年・月・日のパラメータが指定されていない場合は現在の日付を使用
        $currentYear = $year ?? $now->year;
        $currentMonth = $month ?? $now->month;
        $currentDay = $day ?? $now->day;

        // 指定された日付をCarbonオブジェクトとして作成
        $currentDate = Carbon::create($currentYear, $currentMonth, $currentDay);

        // 前日・翌日の計算
        $prevDate = $currentDate->copy()->subDay();
        $nextDate = $currentDate->copy()->addDay();

        $prevYear = $prevDate->year;
        $prevMonth = $prevDate->month;
        $prevDay = $prevDate->day;
        $nextYear = $nextDate->year;
        $nextMonth = $nextDate->month;
        $nextDay = $nextDate->day;

        // 指定された日付の全ユーザーの勤怠データを取得
        $attendances = Attendance::with(['user', 'breaks'])
            ->where('date', $currentDate->format(self::DATE_FORMAT))
            ->orderBy('user_id', 'asc')
            ->get()
            ->map(function ($attendance) {
                // 休憩時間の合計を計算
                $totalBreakMinutes = $this->calculateBreakTime($attendance);

                // 勤務時間の合計を計算
                $totalWorkMinutes = $this->calculateWorkTime($attendance, $totalBreakMinutes);

                // 時間フォーマット（H:i形式）
                $attendance->total_break_time = $this->formatMinutesToTime($totalBreakMinutes);
                $attendance->total_work_time = $this->formatMinutesToTime($totalWorkMinutes);

                // 出勤・退勤時間のフォーマット
                $attendance->formatted_clock_in_time = $attendance->clock_in_time
                    ? $attendance->clock_in_time->format(self::TIME_FORMAT)
                    : null;
                $attendance->formatted_clock_out_time = $attendance->clock_out_time
                    ? $attendance->clock_out_time->format(self::TIME_FORMAT)
                    : null;

                return $attendance;
            });

        // 全ユーザーを取得（勤怠がないユーザーも含める）
        $allUsers = User::whereHas('role', function ($query) {
            $query->where('name', \App\Models\Role::NAME_USER);
        })->orderBy('id', 'asc')->get();

        // ユーザーごとに勤怠データをマッピング
        $attendanceMap = $attendances->keyBy('user_id');
        $attendanceList = [];

        foreach ($allUsers as $user) {
            $attendance = $attendanceMap->get($user->id);
            if ($attendance) {
                $attendanceList[] = $attendance;
            } else {
                // 勤怠がないユーザーも空のレコードとして追加
                $attendanceList[] = (object)[
                    'user' => $user,
                    'formatted_clock_in_time' => null,
                    'formatted_clock_out_time' => null,
                    'total_break_time' => null,
                    'total_work_time' => null,
                    'id' => null,
                ];
            }
        }

        // 表示用の日付フォーマット
        $displayDate = $currentDate->format(self::DISPLAY_DATE_FORMAT);
        $dayOfWeek = self::WEEKDAY_NAMES[$currentDate->dayOfWeek];

        return view('admin.attendance.list', [
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'currentDay' => $currentDay,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'prevDay' => $prevDay,
            'nextYear' => $nextYear,
            'nextMonth' => $nextMonth,
            'nextDay' => $nextDay,
            'displayDate' => $displayDate,
            'dayOfWeek' => $dayOfWeek,
            'attendances' => collect($attendanceList),
        ]);
    }

    /**
     * 管理者勤怠詳細画面を表示
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // TODO: PG09 管理者勤怠詳細画面の実装
        // 機能要件: US011, FN037, FN038, FN039, FN040 を参照
        
        return view('admin.attendance.show', [
            'id' => $id,
        ]);
    }
}

