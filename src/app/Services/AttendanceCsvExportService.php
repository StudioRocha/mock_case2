<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class AttendanceCsvExportService
{
    /**
     * 月次勤怠データをCSV形式で出力
     *
     * @param User $user
     * @param int $year
     * @param int $month
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportMonthlyAttendance(User $user, int $year, int $month)
    {
        // 指定された年月の勤怠データを取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();

        // CSVファイル名を生成（例: 山田太郎_2025_11.csv）
        $fileName = sprintf(
            '%s_%d_%02d.csv',
            $user->name,
            $year,
            $month
        );

        // CSVヘッダー
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ];

        // CSVデータをストリームで出力
        return Response::streamDownload(function () use ($attendances, $user, $year, $month) {
            $handle = fopen('php://output', 'w');

            // BOMを追加（Excelで文字化けを防ぐ）
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSVヘッダー行
            fputcsv($handle, [
                '日付',
                '曜日',
                '出勤時刻',
                '退勤時刻',
                '休憩時間',
                '勤務時間',
            ]);

            // 各勤怠データをCSV形式で出力
            foreach ($attendances as $attendance) {
                // 休憩時間の合計を計算（分）
                $totalBreakMinutes = $this->calculateBreakTime($attendance);
                
                // 勤務時間の合計を計算（分）
                $totalWorkMinutes = $this->calculateWorkTime($attendance, $totalBreakMinutes);

                // 日付フォーマット（Y/m/d形式）
                $date = $attendance->date->format('Y/m/d');
                
                // 曜日
                $dayOfWeek = $this->getDayOfWeekName($attendance->date->dayOfWeek);
                
                // 出勤時刻（H:i形式、存在する場合のみ）
                $clockInTime = $attendance->clock_in_time 
                    ? $attendance->clock_in_time->format('H:i') 
                    : '';
                
                // 退勤時刻（H:i形式、存在する場合のみ）
                $clockOutTime = $attendance->clock_out_time 
                    ? $attendance->clock_out_time->format('H:i') 
                    : '';
                
                // 休憩時間（H:i形式）
                $breakTime = $this->formatMinutesToTime($totalBreakMinutes);
                
                // 勤務時間（H:i形式）
                $workTime = $this->formatMinutesToTime($totalWorkMinutes);

                // CSV行を出力
                fputcsv($handle, [
                    $date,
                    $dayOfWeek,
                    $clockInTime,
                    $clockOutTime,
                    $breakTime,
                    $workTime,
                ]);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    /**
     * 休憩時間の合計を計算（分）
     *
     * @param Attendance $attendance
     * @return int
     */
    private function calculateBreakTime(Attendance $attendance): int
    {
        $totalMinutes = 0;
        
        foreach ($attendance->breaks as $break) {
            if ($break->break_start_time && $break->break_end_time) {
                $start = Carbon::parse($break->break_start_time);
                $end = Carbon::parse($break->break_end_time);
                $totalMinutes += $end->diffInMinutes($start);
            }
        }
        
        return $totalMinutes;
    }

    /**
     * 勤務時間の合計を計算（分）
     *
     * @param Attendance $attendance
     * @param int $totalBreakMinutes
     * @return int
     */
    private function calculateWorkTime(Attendance $attendance, int $totalBreakMinutes): int
    {
        if (!$attendance->clock_in_time || !$attendance->clock_out_time) {
            return 0;
        }

        $clockIn = Carbon::parse($attendance->clock_in_time);
        $clockOut = Carbon::parse($attendance->clock_out_time);
        
        $totalMinutes = $clockOut->diffInMinutes($clockIn);
        
        return max(0, $totalMinutes - $totalBreakMinutes);
    }

    /**
     * 分を時間形式（H:i）に変換
     *
     * @param int $minutes
     * @return string
     */
    private function formatMinutesToTime(int $minutes): string
    {
        if ($minutes <= 0) {
            return '';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        return sprintf('%d:%02d', $hours, $mins);
    }

    /**
     * 曜日名を取得
     *
     * @param int $dayOfWeek
     * @return string
     */
    private function getDayOfWeekName(int $dayOfWeek): string
    {
        $weekdayNames = [
            0 => '日',
            1 => '月',
            2 => '火',
            3 => '水',
            4 => '木',
            5 => '金',
            6 => '土',
        ];

        return $weekdayNames[$dayOfWeek] ?? '';
    }
}

