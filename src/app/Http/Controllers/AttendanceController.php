<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\BreakCorrection;
use App\Http\Requests\AttendanceCorrectionRequest;
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
     * 日付・時間フォーマット定数
     */
    private const DATE_FORMAT = 'Y-m-d';
    private const TIME_FORMAT = 'H:i';
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const DISPLAY_DATE_FORMAT = 'Y年n月j日';

    /**
     * 今日または昨日の勤怠レコードを取得（日付跨ぎ対応）
     *
     * @param int $userId
     * @return Attendance|null
     */
    private function getCurrentOrYesterdayAttendance($userId)
    {
        $today = Carbon::now()->format(self::DATE_FORMAT);
        
        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();
        
        // 今日のレコードが存在しない場合、前日のレコードでまだ退勤していないものを探す（日付跨ぎ対応）
        if (!$attendance) {
            $yesterday = Carbon::yesterday()->format(self::DATE_FORMAT);
            $attendance = Attendance::where('user_id', $userId)
                ->where('date', $yesterday)
                ->whereNotNull('clock_in_time')
                ->whereNull('clock_out_time')
                ->first();
        }
        
        return $attendance;
    }

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
                $start = Carbon::parse($break->break_start_time);
                $end = Carbon::parse($break->break_end_time);
                $totalBreakMinutes += $end->diffInMinutes($start);
            }
        }
        return $totalBreakMinutes;
    }

    /**
     * 勤務時間の合計（分）を計算
     *
     * @param Attendance $attendance
     * @param int $totalBreakMinutes
     * @return int
     */
    private function calculateWorkTime($attendance, $totalBreakMinutes)
    {
        if (!$attendance->clock_in_time || !$attendance->clock_out_time) {
            return 0;
        }
        $clockIn = Carbon::parse($attendance->clock_in_time);
        $clockOut = Carbon::parse($attendance->clock_out_time);
        return $clockOut->diffInMinutes($clockIn) - $totalBreakMinutes;
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
     * ステータスからラベルを取得
     *
     * @param int $status
     * @return string
     */
    private function getStatusLabel($status)
    {
        return self::STATUS_LABELS[$status] ?? self::STATUS_LABELS[Attendance::STATUS_OFF_DUTY];
    }

    /**
     * 勤怠登録画面を表示
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $now = Carbon::now();
        
        // 現在の日時情報を取得
        $weekday = self::WEEKDAY_NAMES[$now->dayOfWeek];
        $date = $now->format(self::DISPLAY_DATE_FORMAT) . '(' . $weekday . ')';
        $time = $now->format(self::TIME_FORMAT);
        
        // 今日または昨日の勤怠レコードを取得
        $attendance = $this->getCurrentOrYesterdayAttendance(Auth::id());
        
        // ステータスを判定
        if (!$attendance) {
            $status = Attendance::STATUS_OFF_DUTY;
            $statusLabel = $this->getStatusLabel(Attendance::STATUS_OFF_DUTY);
        } else {
            $status = $attendance->status;
            $statusLabel = $this->getStatusLabel($status);
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
        $today = $now->format(self::DATE_FORMAT);
        $clockInDateTime = $now->format(self::DATETIME_FORMAT);

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        // 既に出勤済みの場合はエラー
        if ($attendance && $attendance->clock_in_time) {
            return redirect()->route('attendance')
                ->with('error', '本日は既に出勤済みです。');
        }

        // 前日のレコードでまだ退勤していないものがある場合はエラー
        // （ステータスがSTATUS_FINISHEDでなければ出勤ボタンは表示されないが、直接アクセス対策）
        $yesterday = Carbon::yesterday()->format(self::DATE_FORMAT);
        $yesterdayAttendance = Attendance::where('user_id', Auth::id())
            ->where('date', $yesterday)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->first();
        
        if ($yesterdayAttendance && $yesterdayAttendance->status !== Attendance::STATUS_FINISHED) {
            return redirect()->route('attendance')
                ->with('error', '前日の勤務がまだ終了していません。');
        }

        // 勤怠レコードを作成または更新
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'date' => $today,
                'clock_in_time' => $clockInDateTime,
                'status' => Attendance::STATUS_WORKING,
                // noteは設定しない（NULLのまま）
            ]);
        } else {
            $attendance->update([
                'clock_in_time' => $clockInDateTime,
                'status' => Attendance::STATUS_WORKING,
            ]);
        }

        return redirect()->route('attendance');
    }

    /**
     * 退勤処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockOut()
    {
        $now = Carbon::now();
        $clockOutDateTime = $now->format(self::DATETIME_FORMAT);

        // 今日または昨日の勤怠レコードを取得
        $attendance = $this->getCurrentOrYesterdayAttendance(Auth::id());

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

    /**
     * 休憩開始処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function breakStart()
    {
        $now = Carbon::now();
        $breakStartDateTime = $now->format(self::DATETIME_FORMAT);

        // 今日または昨日の勤怠レコードを取得
        $attendance = $this->getCurrentOrYesterdayAttendance(Auth::id());

        // 勤怠レコードが存在しない、または出勤中でない場合はエラー
        if (!$attendance) {
            return redirect()->route('attendance')
                ->with('error', '出勤記録がありません。');
        }

        if ($attendance->status !== Attendance::STATUS_WORKING) {
            return redirect()->route('attendance')
                ->with('error', '休憩を開始できる状態ではありません。');
        }

        // 休憩レコードを作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $breakStartDateTime,
        ]);

        // 勤怠ステータスを休憩中に変更
        $attendance->update([
            'status' => Attendance::STATUS_BREAK,
        ]);

        return redirect()->route('attendance');
    }

    /**
     * 休憩終了処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function breakEnd()
    {
        $now = Carbon::now();
        $today = $now->format(self::DATE_FORMAT);
        $breakEndDateTime = $now->format(self::DATETIME_FORMAT);

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        // 勤怠レコードが存在しない、または休憩中でない場合はエラー
        if (!$attendance) {
            return redirect()->route('attendance')
                ->with('error', '出勤記録がありません。');
        }

        if ($attendance->status !== Attendance::STATUS_BREAK) {
            return redirect()->route('attendance')
                ->with('error', '休憩を終了できる状態ではありません。');
        }

        // 最新の休憩レコード（break_end_timeがNULLのもの）を取得
        $breakTime = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end_time')
            ->orderBy('break_start_time', 'desc')
            ->first();

        if (!$breakTime) {
            return redirect()->route('attendance')
                ->with('error', '休憩記録が見つかりません。');
        }

        // 休憩終了時刻を記録
        $breakTime->update([
            'break_end_time' => $breakEndDateTime,
        ]);

        // 勤怠ステータスを出勤中に変更
        $attendance->update([
            'status' => Attendance::STATUS_WORKING,
        ]);

        return redirect()->route('attendance');
    }

    /**
     * 勤怠一覧画面を表示
     *
     * @param int|null $year
     * @param int|null $month
     * @return \Illuminate\View\View
     */
    public function list($year = null, $month = null)
    {
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
        $attendances = Attendance::where('user_id', Auth::id())
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
                $attendance->formatted_date = Carbon::parse($attendance->date)->format('m/d');
                $attendance->day_of_week = self::WEEKDAY_NAMES[Carbon::parse($attendance->date)->dayOfWeek];
                
                // 出勤・退勤時間のフォーマット
                $attendance->formatted_clock_in_time = $attendance->clock_in_time 
                    ? Carbon::parse($attendance->clock_in_time)->format(self::TIME_FORMAT) 
                    : null;
                $attendance->formatted_clock_out_time = $attendance->clock_out_time 
                    ? Carbon::parse($attendance->clock_out_time)->format(self::TIME_FORMAT) 
                    : null;
                
                return $attendance;
            });
        
        return view('attendance.list', [
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
     * 勤怠詳細画面を表示
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function detail($id)
    {
        // 勤怠レコードを取得（ユーザー情報と休憩レコードも一緒に取得）
        // 休憩レコードは開始時間と終了時間の両方が存在するもののみ取得
        $attendance = Attendance::with([
            'user',
            'breaks' => function ($query) {
                $query->whereNotNull('break_start_time')
                      ->whereNotNull('break_end_time');
            },
            'stampCorrectionRequests'
        ])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        // 自分の勤怠のみ閲覧可能
        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ちの修正申請があるかチェック
        $pendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->first();
        
        $hasPendingRequest = $pendingRequest !== null;
        
        // 承認待ちの修正申請がある場合は、requested_*を表示用として使用
        $displayClockInTime = $hasPendingRequest && $pendingRequest->requested_clock_in_time
            ? Carbon::parse($pendingRequest->requested_clock_in_time)->format(self::TIME_FORMAT)
            : ($attendance->clock_in_time ? Carbon::parse($attendance->clock_in_time)->format(self::TIME_FORMAT) : '');
        
        $displayClockOutTime = $hasPendingRequest && $pendingRequest->requested_clock_out_time
            ? Carbon::parse($pendingRequest->requested_clock_out_time)->format(self::TIME_FORMAT)
            : ($attendance->clock_out_time ? Carbon::parse($attendance->clock_out_time)->format(self::TIME_FORMAT) : '');
        
        $displayNote = $hasPendingRequest && $pendingRequest->requested_note 
            ? $pendingRequest->requested_note 
            : ($attendance->note ?? '');

        // 休憩時間の集計（詳細情報も含む）
        $totalBreakMinutes = 0;
        $breakDetails = [];
        
        foreach ($attendance->breaks as $break) {
            // 開始時間と終了時間の両方が存在し、有効な値であることを確認
            if (filled($break->break_start_time) && filled($break->break_end_time)) {
                try {
                    $start = Carbon::parse($break->break_start_time);
                    $end = Carbon::parse($break->break_end_time);
                    
                    // 有効な日時であることを確認
                    if ($start->isValid() && $end->isValid()) {
                        $breakMinutes = $end->diffInMinutes($start);
                        $totalBreakMinutes += $breakMinutes;
                        
                        // 休憩時間をH:i形式に変換
                        $breakTime = $this->formatMinutesToTime($breakMinutes);
                        
                        $breakDetails[] = [
                            'start_time' => $start->format(self::TIME_FORMAT),
                            'end_time' => $end->format(self::TIME_FORMAT),
                            'break_time' => $breakTime,
                            'minutes' => $breakMinutes,
                        ];
                    }
                } catch (\Exception $e) {
                    // パースエラーが発生した場合はスキップ
                    continue;
                }
            }
            // 開始時間のみ、または終了時間のみ、または両方null/空の場合は表示しない
        }

        // 休憩時間の合計をH:i形式に変換
        $totalBreakTime = $this->formatMinutesToTime($totalBreakMinutes);

        // 勤務時間の合計を計算
        $totalWorkMinutes = $this->calculateWorkTime($attendance, $totalBreakMinutes);

        // 勤務時間をH:i形式に変換
        $totalWorkTime = $this->formatMinutesToTime($totalWorkMinutes);

        // 日付のフォーマット（2023年 6月1日形式）
        $date = Carbon::parse($attendance->date);
        $formattedDate = $date->format('Y年') . '&nbsp;' . $date->format('n月j日');

        return view('attendance.detail', [
            'attendance' => $attendance,
            'formattedDate' => $formattedDate,
            'breakDetails' => $breakDetails,
            'totalBreakTime' => $totalBreakTime,
            'totalWorkTime' => $totalWorkTime,
            'hasPendingRequest' => $hasPendingRequest,
            'canEdit' => !$hasPendingRequest,
            'displayClockInTime' => $displayClockInTime,
            'displayClockOutTime' => $displayClockOutTime,
            'displayNote' => $displayNote,
        ]);
    }

    /**
     * 修正申請のバリデーション
     *
     * @param Attendance $attendance
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function validateCorrectionRequest($attendance, $id)
    {
        // 自分の勤怠のみ申請可能
        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ちの修正申請があるかチェック
        $hasPendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($hasPendingRequest) {
            return redirect()->route('attendance.detail', ['id' => $id])
                ->with('error', '承認待ちのため修正はできません。');
        }

        return null;
    }

    /**
     * 修正申請レコードを作成
     *
     * @param Attendance $attendance
     * @param AttendanceCorrectionRequest $request
     * @return StampCorrectionRequest
     */
    private function createCorrectionRequest($attendance, $request)
    {
        // 日をまたぐ勤怠かどうかを判定（元の勤怠レコードから）
        $isOvernight = $attendance->isOvernight();

        // 出勤時間の日時を作成
        $requestedClockInTime = null;
        if ($request->clock_in_time) {
            $requestedClockInTime = Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $request->clock_in_time);
        }

        // 退勤時間の日時を作成（日をまたぐ場合は翌日として扱う）
        $requestedClockOutTime = null;
        if ($request->clock_out_time) {
            $baseDate = Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $request->clock_out_time);
            // 日をまたぐ勤怠の場合、すべてのケースを翌日として扱う
            if ($isOvernight && $requestedClockInTime) {
                // 退勤時間が出勤時間より小さい場合：翌日として扱う（例：23:00 → 02:00）
                // 退勤時間が出勤時間より大きい場合：翌日として扱う（例：23:00 → 23:30）
                // 退勤時間 = 出勤時間の場合：翌日として扱う（24時間勤務、例：23:00 → 23:00）
                $requestedClockOutTime = $baseDate->copy()->addDay();
            } else {
                $requestedClockOutTime = $baseDate;
            }
        }

        // 修正申請レコードを作成
        return StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_time' => $requestedClockInTime,
            'requested_clock_out_time' => $requestedClockOutTime,
            'requested_note' => $request->note,
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);
    }

    /**
     * 休憩修正レコードを作成
     *
     * @param StampCorrectionRequest $correctionRequest
     * @param Attendance $attendance
     * @param AttendanceCorrectionRequest $request
     * @return void
     */
    private function createBreakCorrections($correctionRequest, $attendance, $request)
    {
        $breakStartTimes = $request->input('break_start_times', []);
        $breakEndTimes = $request->input('break_end_times', []);

        foreach ($breakStartTimes as $index => $breakStartTime) {
            $breakEndTime = $breakEndTimes[$index] ?? null;

            // 両方入力されている場合のみ作成
            if ($breakStartTime && $breakEndTime) {
                BreakCorrection::create([
                    'stamp_correction_request_id' => $correctionRequest->id,
                    'requested_break_start_time' => Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $breakStartTime),
                    'requested_break_end_time' => Carbon::parse($attendance->date->format(self::DATE_FORMAT) . ' ' . $breakEndTime),
                ]);
            }
        }
    }

    /**
     * 修正申請を実行
     *
     * @param int $id
     * @param AttendanceCorrectionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function correctionRequest($id, AttendanceCorrectionRequest $request)
    {
        // 勤怠レコードを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->findOrFail($id);

        // バリデーション
        $validationError = $this->validateCorrectionRequest($attendance, $id);
        if ($validationError) {
            return $validationError;
        }

        DB::beginTransaction();
        try {
            // 修正申請レコードを作成
            $correctionRequest = $this->createCorrectionRequest($attendance, $request);

            // 休憩修正レコードを作成
            $this->createBreakCorrections($correctionRequest, $attendance, $request);

            DB::commit();

            return redirect()->route('attendance.detail', ['id' => $id])
                ->with('success', '修正申請が完了しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('attendance.detail', ['id' => $id])
                ->with('error', '修正申請の処理中にエラーが発生しました。');
        }
    }
}
