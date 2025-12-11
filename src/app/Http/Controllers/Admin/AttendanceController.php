<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\PreparesAttendanceDetailData;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Services\AttendanceCsvExportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // トレイト参照
    use PreparesAttendanceDetailData;
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
     * 管理者勤怠詳細画面を表示（FN037: 詳細情報取得機能）
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // 勤怠レコードを取得（ユーザー情報と休憩レコードも一緒に取得）
        // 管理者は全ユーザーの勤怠を閲覧可能
        $attendance = Attendance::with([
            'user',
            'breaks' => function ($query) {
                $query->whereNotNull('break_start_time')
                      ->whereNotNull('break_end_time');
            },
            'stampCorrectionRequests'
        ])->findOrFail($id);

        // 承認待ちの修正申請があるかチェック
        $hasPendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', \App\Models\StampCorrectionRequest::STATUS_PENDING)
            ->exists();

        // 共通のデータ準備メソッドを使用
        // 管理者の勤怠詳細画面では、実際の勤怠データを表示する（FN037: 実際の勤怠内容が反映されていること）
        // 承認待ちの修正申請がある場合でも、実際の勤怠データを表示し、修正申請の内容は修正申請詳細画面（US015）で確認する
        $data = $this->prepareAttendanceDetailData(
            $attendance,  // 第1引数: 勤怠レコード
            false         // 第2引数: 承認待ちチェックフラグ（false=チェックしない、実際の勤怠データを表示）
        );
        
        $data['hasPendingRequest'] = $hasPendingRequest;
        $data['canEdit'] = !$hasPendingRequest;

        return view('admin.attendance.detail', $data);
    }

    /**
     * 管理者として勤怠情報を直接修正（FN040: 修正機能）
     *
     * @param int $id
     * @param AdminAttendanceUpdateRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($id, AdminAttendanceUpdateRequest $request)
    {
        // 勤怠レコードを取得
        $attendance = Attendance::findOrFail($id);

        // 承認待ちの修正申請がある場合は修正不可（バリデーションでチェック済み）
        $hasPendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', \App\Models\StampCorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($hasPendingRequest) {
            return redirect()->route('admin.attendance.show', ['id' => $id])
                ->with('error', '*承認待ちのため修正はできません。');
        }

        DB::beginTransaction();
        try {
            // 出勤時間の更新
            if ($request->clock_in_time) {
                $clockInDateTime = Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $request->clock_in_time);
                $attendance->clock_in_time = $clockInDateTime;
            } else {
                $attendance->clock_in_time = null;
            }

            // 退勤時間の更新（日跨ぎ対応）
            if ($request->clock_out_time) {
                // 日跨ぎかどうかを判定
                $isOvernight = false;
                if ($request->clock_in_time && $request->clock_out_time) {
                    $clockIn = Carbon::createFromFormat('H:i', $request->clock_in_time);
                    $clockOut = Carbon::createFromFormat('H:i', $request->clock_out_time);
                    $isOvernight = $clockOut->lessThan($clockIn) || $clockOut->equalTo($clockIn);
                }

                $baseClockOutDateTime = Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $request->clock_out_time);
                $attendance->clock_out_time = $isOvernight ? $baseClockOutDateTime->copy()->addDay() : $baseClockOutDateTime;
            } else {
                $attendance->clock_out_time = null;
            }

            // 備考の更新
            $attendance->note = $request->note;

            $attendance->save();

            // 既存の休憩レコードを削除
            $attendance->breaks()->delete();

            // 新しい休憩レコードを作成
            $breakStartTimes = $request->input('break_start_times', []);
            $breakEndTimes = $request->input('break_end_times', []);

            foreach ($breakStartTimes as $index => $breakStartTime) {
                $breakEndTime = $breakEndTimes[$index] ?? null;

                // 両方入力されている場合のみ作成
                if ($breakStartTime && $breakEndTime) {
                    $breakStart = Carbon::createFromFormat('H:i', $breakStartTime);
                    $breakEnd = Carbon::createFromFormat('H:i', $breakEndTime);
                    
                    // 休憩時間が日跨ぎかどうかを判定
                    $isBreakOvernight = $breakEnd->lessThan($breakStart) || $breakEnd->equalTo($breakStart);
                    
                    // 休憩開始時間の日時を作成
                    $breakStartDateTime = Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $breakStartTime);
                    
                    // 休憩終了時間の日時を作成（日跨ぎの場合は翌日として扱う）
                    $baseBreakEndDateTime = Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $breakEndTime);
                    $breakEndDateTime = $isBreakOvernight ? $baseBreakEndDateTime->copy()->addDay() : $baseBreakEndDateTime;
                    
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start_time' => $breakStartDateTime,
                        'break_end_time' => $breakEndDateTime,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.attendance.show', ['id' => $id])
                ->with('success', '勤怠情報を修正しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.attendance.show', ['id' => $id])
                ->with('error', '修正処理中にエラーが発生しました。');
        }
    }

    /**
     * スタッフ別勤怠一覧画面（月次）を表示（PG11）
     * FN043: 勤怠一覧情報取得機能、FN044: 月表示変更機能
     *
     * @param int $id ユーザーID
     * @param int|null $year
     * @param int|null $month
     * @return \Illuminate\View\View
     */
    public function monthly($id, $year = null, $month = null)
    {
        // ユーザー情報を取得
        $user = User::findOrFail($id);
        
        $now = Carbon::now();
        
        // 年・月のパラメータが指定されていない場合は現在の年月を使用
        $currentYear = $year ?? $now->year;
        $currentMonth = $month ?? $now->month;
        
        // 前月・翌月の計算
        $prevDate = Carbon::create($currentYear, $currentMonth, 1)->subMonth();
        $nextDate = Carbon::create($currentYear, $currentMonth, 1)->addMonth();
        
        $prevYear = $prevDate->year;
        $prevMonth = $prevDate->month;
        $nextYear = $nextDate->year;
        $nextMonth = $nextDate->month;
        
        // 指定された年月の勤怠データを取得（日付順：古い日から順）
        // 休憩レコードも一緒に取得（eager loading）
        $attendances = Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($attendance) {
                // 休憩時間の合計を計算
                $totalBreakMinutes = $this->calculateBreakTime($attendance);
                
                // 勤務時間の合計を計算
                $totalWorkMinutes = $this->calculateWorkTime($attendance, $totalBreakMinutes);
                
                // 時間フォーマット（H:i形式）
                $attendance->total_break_time = $this->formatMinutesToTime($totalBreakMinutes);
                $attendance->total_work_time = $this->formatMinutesToTime($totalWorkMinutes);
                
                // 日付フォーマット（m/d形式）
                $attendance->formatted_date = $attendance->date->format('m/d');
                $attendance->day_of_week = self::WEEKDAY_NAMES[$attendance->date->dayOfWeek];
                
                // 出勤・退勤時間のフォーマット
                $attendance->formatted_clock_in_time = $attendance->clock_in_time 
                    ? $attendance->clock_in_time->format('H:i') 
                    : null;
                $attendance->formatted_clock_out_time = $attendance->clock_out_time 
                    ? $attendance->clock_out_time->format('H:i') 
                    : null;
                
                return $attendance;
            });
        
        return view('admin.attendance.monthly', [
            'user' => $user,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear' => $nextYear,
            'nextMonth' => $nextMonth,
            'attendances' => $attendances,
        ]);
    }

    /**
     * スタッフ別月次勤怠データをCSV形式で出力（FN045: CSV出力機能）
     *
     * @param int $id
     * @param int|null $year
     * @param int|null $month
     * @param AttendanceCsvExportService $csvExportService
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportMonthlyAttendance($id, $year = null, $month = null, AttendanceCsvExportService $csvExportService)
    {
        // ユーザー情報を取得
        $user = User::findOrFail($id);
        
        $now = Carbon::now();
        
        // 年・月のパラメータが指定されていない場合は現在の年月を使用
        $currentYear = $year ?? $now->year;
        $currentMonth = $month ?? $now->month;
        
        // CSV出力サービスを使用してCSVを生成・ダウンロード
        return $csvExportService->exportMonthlyAttendance($user, $currentYear, $currentMonth);
    }
}

