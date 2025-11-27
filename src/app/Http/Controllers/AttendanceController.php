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
     * ステータスラベルのマッピング　/クラス定数
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
     * アクティブな（進行中の）勤怠レコードを取得
     * 今日・昨日の未退勤レコードを検索（日付跨ぎ対応）
     *
     * @param int $userId
     * @return Attendance|null
     */
    private function getActiveAttendance($userId)
    {
        $today = Carbon::now()->format(self::DATE_FORMAT);
        
        // ① 今日の勤怠レコードを取得（最速）
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();
        
        if ($attendance) {
            return $attendance;
        }
        
        // ② 昨日の未退勤レコードを取得（高速）
        $yesterday = Carbon::yesterday()->format(self::DATE_FORMAT);
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $yesterday)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->first();
        
        if ($attendance) {
            return $attendance;
        }
        
        // 今日と昨日のいずれでも見つからなかった場合はnullを返す
        return null;
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
                // break_start_time/break_end_timeは$castsでdatetimeに設定されているため、既にCarbonオブジェクト
                $totalBreakMinutes += $break->break_end_time->diffInMinutes($break->break_start_time);
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
        // 出勤時刻がない、または退勤時刻がない場合に 0 を返す
        if (!$attendance->clock_in_time || !$attendance->clock_out_time) {
            return 0;
        }
        // clock_in_time/clock_out_timeは$castsでdatetimeに設定されているため、既にCarbonオブジェクト
        return $attendance->clock_out_time->diffInMinutes($attendance->clock_in_time) - $totalBreakMinutes;
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
    // ① 現在の日時を取得
    $now = Carbon::now();
    
    // ② 曜日を取得（0=日曜日、6=土曜日）
    $weekday = self::WEEKDAY_NAMES[$now->dayOfWeek];
    
    // ③ 日付をフォーマット
    $date = $now->format(self::DISPLAY_DATE_FORMAT) . '(' . $weekday . ')';
    
    // ④ 時刻をフォーマット（
    $time = $now->format(self::TIME_FORMAT);
    
    // ⑤ アクティブな（進行中の）勤怠レコードを取得
    $attendance = $this->getActiveAttendance(Auth::id());
    
    // ⑥ アクティブなレコードが見つからない場合、今日の退勤済みレコードを取得（「お疲れ様でした」表示用）
    if (!$attendance) {
        $today = Carbon::now()->format(self::DATE_FORMAT);
        $finishedAttendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->whereNotNull('clock_in_time')
            ->whereNotNull('clock_out_time')
            ->first();
        
        if ($finishedAttendance) {
            $attendance = $finishedAttendance;
        }
    }
    
    // ⑦ ステータスを判定
    if (!$attendance) {
        $status = Attendance::STATUS_OFF_DUTY;
        $statusLabel = '勤務外';
    } else {
        $status = $attendance->status;
        $statusLabel = $this->getStatusLabel($status);
    }
    
    // ⑧ ビューにデータを渡す
    return view('attendance.index', [
        'date' => $date,        // "2024年11月15日(金)"
        'time' => $time,        // "09:30"
        'status' => $status,     // 0, 1, 2, 3
        'statusLabel' => $statusLabel,  // "勤務外" etc.
    ]);
  
}

## ステータス定義

// `Attendance`モデルで定義されている4つのステータス定数：

// | 定数名 | 値 | ラベル | 説明 |
// |--------|-----|--------|------|
// | `STATUS_OFF_DUTY` | 0 | 勤務外 | まだ出勤していない状態 |
// | `STATUS_WORKING` | 1 | 出勤中 | 出勤ボタンを押した後、休憩中でも退勤済みでもない状態 |
// | `STATUS_BREAK` | 2 | 休憩中 | 休憩ボタンを押した状態 |
// | `STATUS_FINISHED` | 3 | 退勤済 | 退勤ボタンを押した後の状態 |

    /**
     * 出勤処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockIn()
    {
        // 現在の日時を取得
        $now = Carbon::now();
        // 今日の日付をフォーマット（Y-m-d形式、例: 2024-01-16）
        $today = $now->format(self::DATE_FORMAT);
        // 出勤日時をフォーマット（Y-m-d H:i:s形式、例: 2024-01-16 09:00:00）
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

        // 勤怠レコードを作成
        // 注意: UI上ではレコードが存在する状態で出勤ボタンは表示されないが、
        // 直接アクセスやデータ不整合の可能性を考慮してガードを実装
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'date' => $today,
                'clock_in_time' => $clockInDateTime,
                'status' => Attendance::STATUS_WORKING,
                // noteは設定しない（NULLのまま）
            ]);
        } else {
            // レコードが存在するがclock_in_timeがNULLの場合（通常は発生しない）
            // データ不整合の可能性があるため、エラーを返す
            return redirect()->route('attendance')
                ->with('error', '予期しないエラーが発生しました。管理者にお問い合わせください。');
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
        $today = $now->format(self::DATE_FORMAT);

        // アクティブな（進行中の）勤怠レコードをローカル変数として取得
        $attendance = $this->getActiveAttendance(Auth::id());

        // 勤怠レコードが存在しない場合はエラー
        if (!$attendance) {
            return redirect()->route('attendance')
                ->with('error', '出勤記録がありません。');
        }

        // 既に退勤済みの場合はエラー（日跨ぎの場合も考慮）
        if ($attendance->clock_out_time) {
            // 今日のレコードかどうかでエラーメッセージを分ける
            if ($attendance->date === $today) {
                return redirect()->route('attendance')
                    ->with('error', '本日は既に退勤済みです。');
            } else {
                return redirect()->route('attendance')
                    ->with('error', '既に退勤済みです。');
            }
        }

        // 出勤中または休憩中の状態でない場合はエラー
        if (!in_array($attendance->status, [Attendance::STATUS_WORKING, Attendance::STATUS_BREAK])) {
            return redirect()->route('attendance')
                ->with('error', '退勤できる状態ではありません。');
        }

        // 退勤処理：出勤時に作成されたレコードに退勤時刻（clock_out_time）とステータスを設定
        // clock_in_timeとclock_out_timeは別々のカラムのため、退勤時刻を設定する処理
        // （日付跨ぎの可能性を考慮して実際の日時を保存）
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

        // アクティブな（進行中の）勤怠レコードを取得
        $attendance = $this->getActiveAttendance(Auth::id());

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
                // dateは$castsでdateに設定されているため、既にCarbonオブジェクト
                $attendance->formatted_date = $attendance->date->format('m/d');
                $attendance->day_of_week = self::WEEKDAY_NAMES[$attendance->date->dayOfWeek];
                
                // 出勤・退勤時間のフォーマット
                // clock_in_time/clock_out_timeは$castsでdatetimeに設定されているため、既にCarbonオブジェクト
                $attendance->formatted_clock_in_time = $attendance->clock_in_time 
                    ? $attendance->clock_in_time->format(self::TIME_FORMAT) 
                    : null;
                $attendance->formatted_clock_out_time = $attendance->clock_out_time 
                    ? $attendance->clock_out_time->format(self::TIME_FORMAT) 
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

        // 権限チェック:自分の勤怠のみ閲覧可能
        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ちの修正申請があるかチェック
        $pendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->first();
        
        $hasPendingRequest = $pendingRequest !== null;
        
        // 承認待ちの修正申請がある場合は、requested_*を表示用として使用
        // 表示優先順位: 承認待ちの申請値 > 元の勤怠データ > 空文字
        
        // 出勤時刻の表示値を決定
        // ① 承認待ちの修正申請があり、かつ申請された出勤時間が存在する場合
        //    → 申請された出勤時間をH:i形式でフォーマット
        // ② それ以外の場合（承認待ちがない、または申請された出勤時間がない）
        //    → 元の勤怠データの出勤時間があればH:i形式でフォーマット、なければ空文字
        $displayClockInTime = $hasPendingRequest && $pendingRequest->requested_clock_in_time
            ? $pendingRequest->requested_clock_in_time->format(self::TIME_FORMAT)
            : ($attendance->clock_in_time ? $attendance->clock_in_time->format(self::TIME_FORMAT) : '');
        
        // 退勤時刻の表示値を決定
        // ① 承認待ちの修正申請があり、かつ申請された退勤時間が存在する場合
        //    → 申請された退勤時間をH:i形式でフォーマット
        // ② それ以外の場合（承認待ちがない、または申請された退勤時間がない）
        //    → 元の勤怠データの退勤時間があればH:i形式でフォーマット、なければ空文字
        $displayClockOutTime = $hasPendingRequest && $pendingRequest->requested_clock_out_time
            ? $pendingRequest->requested_clock_out_time->format(self::TIME_FORMAT)
            : ($attendance->clock_out_time ? $attendance->clock_out_time->format(self::TIME_FORMAT) : '');
        
        // 備考の表示値を決定
        // ① 承認待ちの修正申請があり、かつ申請された備考が存在する場合
        //    → 申請された備考をそのまま使用
        // ② それ以外の場合（承認待ちがない、または申請された備考がない）
        //    → 元の勤怠データの備考があれば使用、なければ空文字（??演算子でnullを空文字に変換）
        $displayNote = $hasPendingRequest && $pendingRequest->requested_note 
            ? $pendingRequest->requested_note 
            : ($attendance->note ?? '');

        // 休憩時間の集計（詳細情報も含む）
        $totalBreakMinutes = 0;
        $breakDetails = [];
        
        foreach ($attendance->breaks as $break) {
            // 開始時間と終了時間の両方が存在し、有効な値であることを確認
            if (filled($break->break_start_time) && filled($break->break_end_time)) {
                // break_start_time/break_end_timeは$castsでdatetimeに設定されているため、既にCarbonオブジェクト
                $start = $break->break_start_time;
                $end = $break->break_end_time;
                
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
        $formattedDate = $attendance->date->format('Y年') . '&nbsp;' . $attendance->date->format('n月j日');

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
        // バリデーション（権限チェック・承認待ちチェック）はAttendanceCorrectionRequestで実行済み
        $attendance = Attendance::where('user_id', Auth::id())
            ->findOrFail($id);

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
