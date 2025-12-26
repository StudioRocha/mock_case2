<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

trait PreparesAttendanceDetailData
{
    /**
     * 勤怠詳細画面用のデータを準備
     * 
     * 注意: このトレイトを使用するクラスには TIME_FORMAT 定数が必要です
     *
     * @param Attendance $attendance
     * @param bool $checkPendingRequest 承認待ちの修正申請をチェックするかどうか
     * @return array
     */
    protected function prepareAttendanceDetailData(Attendance $attendance, bool $checkPendingRequest = true)
    {
        // 承認待ちの修正申請があるかチェック（管理者の場合はチェックしない）
        $hasPendingRequest = false;
        $pendingRequest = null;
        
        if ($checkPendingRequest) {
            $pendingRequest = $attendance->stampCorrectionRequests()
                ->with('breakCorrections')
                ->where('status', StampCorrectionRequest::STATUS_PENDING)
                ->first();
            
            $hasPendingRequest = $pendingRequest !== null;
        }
        
        // 承認待ちの修正申請がある場合は、requested_*を表示用として使用
        // 表示優先順位: 承認待ちの申請値 > 元の勤怠データ > 空文字
        
        // 出勤時刻の表示値を決定
        $displayClockInTime = $hasPendingRequest && $pendingRequest->requested_clock_in_time
            ? $pendingRequest->requested_clock_in_time->format('H:i')
            : ($attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '');
        
        // 退勤時刻の表示値を決定
        $displayClockOutTime = $hasPendingRequest && $pendingRequest->requested_clock_out_time
            ? $pendingRequest->requested_clock_out_time->format('H:i')
            : ($attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '');
        
        // 備考の表示値を決定
        $displayNote = $hasPendingRequest && $pendingRequest->requested_note 
            ? $pendingRequest->requested_note 
            : ($attendance->note ?? '');

        // 休憩時間の集計（詳細情報も含む）
        $totalBreakMinutes = 0;
        $breakDetails = [];
        
        // 承認待ちの修正申請がある場合は、申請された休憩時間を使用
        if ($hasPendingRequest && $pendingRequest->breakCorrections->isNotEmpty()) {
            // 承認待ちの修正申請の休憩時間を使用
            foreach ($pendingRequest->breakCorrections as $breakCorrection) {
                if ($breakCorrection->requested_break_start_time && $breakCorrection->requested_break_end_time) {
                    $start = $breakCorrection->requested_break_start_time;
                    $end = $breakCorrection->requested_break_end_time;
                    
                    // 有効な日時であることを確認
                    if ($start->isValid() && $end->isValid()) {
                        $breakMinutes = $end->diffInMinutes($start);
                        $totalBreakMinutes += $breakMinutes;
                        
                        // 休憩時間をH:i形式に変換
                        $breakTime = $this->formatMinutesToTime($breakMinutes);
                        
                        $breakDetails[] = [
                            'start_time' => $start->format('H:i'),
                            'end_time' => $end->format('H:i'),
                            'break_time' => $breakTime,
                            'minutes' => $breakMinutes,
                        ];
                    }
                }
            }
        } else {
            // 承認待ちの修正申請がない場合は、元の休憩レコードを使用
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
                            'start_time' => $start->format('H:i'),
                            'end_time' => $end->format('H:i'),
                            'break_time' => $breakTime,
                            'minutes' => $breakMinutes,
                        ];
                    }
                }
            }
        }

        // 修正用に、承認待ちの修正申請がない場合のみ空白の休憩列を1つ追加
        if (!$hasPendingRequest) {
            $breakDetails[] = [
                'start_time' => '',
                'end_time' => '',
                'break_time' => null,
                'minutes' => 0,
            ];
        }

        // 休憩時間の合計をH:i形式に変換
        $totalBreakTime = $this->formatMinutesToTime($totalBreakMinutes);

        // 勤務時間の合計を計算
        $totalWorkMinutes = $this->calculateWorkTime($attendance, $totalBreakMinutes);

        // 勤務時間をH:i形式に変換
        $totalWorkTime = $this->formatMinutesToTime($totalWorkMinutes);

        return [
            'attendance' => $attendance,
            'breakDetails' => $breakDetails,
            'totalBreakTime' => $totalBreakTime,
            'totalWorkTime' => $totalWorkTime,
            'hasPendingRequest' => $hasPendingRequest,
            'canEdit' => !$hasPendingRequest,
            'displayClockInTime' => $displayClockInTime,
            'displayClockOutTime' => $displayClockOutTime,
            'displayNote' => $displayNote,
        ];
    }
}

