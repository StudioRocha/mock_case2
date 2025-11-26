<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceManualDataSeeder extends Seeder
{
    /**
     * 実行時に変数を変更して使用する勤怠データ作成シーダー
     * 
     * 使用方法:
     * 1. 以下の変数を変更
     * 2. php artisan db:seed --class=AttendanceManualDataSeeder を実行
     */

    // ============================================
    // 設定変数（ここを変更してください）
    // ============================================
    
    /**
     * ユーザーID（既存のユーザーIDを指定）
     */
    private $userId = 1;
    
    /**
     * 開始日付（勤怠データを作成する開始日）
     * 形式: 'Y-m-d' (例: '2025-11-01')
     */
    private $startDate = '2025-11-01';
    
    /**
     * 終了日付（勤怠データを作成する終了日）
     * 形式: 'Y-m-d' (例: '2025-11-05')
     */
    private $endDate = '2025-11-01';
    
    /**
     * 出勤時間（各日の出勤時間）
     * 形式: 'H:i' (例: '09:00')
     * 配列で複数日分を指定可能（日数分の要素が必要）
     */
    private $clockInTimes = [
        '09:00',  // 1日目
        // '09:00',  // 2日目
        // '09:00',  // 3日目
        // '09:00',  // 4日目
        // '09:00',  // 5日目
    ];
    
    /**
     * 退勤時間（各日の退勤時間）
     * 形式: 'H:i' (例: '18:00')
     * 配列で複数日分を指定可能（日数分の要素が必要）
     * 日をまたぐ場合は翌日の時刻を指定（例: '02:00' は翌日の02:00）
     */
    private $clockOutTimes = [
        '18:00',  // 1日目
        // '18:00',  // 2日目
        // '18:00',  // 3日目
        // '18:00',  // 4日目
        // '18:00',  // 5日目
    ];
    
    /**
     * 休憩時間（各日の休憩時間）
     * 形式: [
     *   ['start' => '12:00', 'end' => '13:00'],  // 休憩1
     *   ['start' => '15:00', 'end' => '15:30'],  // 休憩2
     * ]
     * 配列の各要素が1日分（日数分の要素が必要）
     * 日をまたぐ休憩の場合は翌日の時刻を指定php
     */
    private $breakTimes = [
        [  // 1日目
            ['start' => '12:00', 'end' => '13:00'],
             ['start' => '16:00', 'end' => '16:20'],
        ],
        // [  // 2日目
        //     ['start' => '12:00', 'end' => '13:00'],
        // ],
        // [  // 3日目
        //     ['start' => '12:00', 'end' => '13:00'],
        // ],
        // [  // 4日目
        //     ['start' => '12:00', 'end' => '13:00'],
        // ],
        // [  // 5日目
        //     ['start' => '12:00', 'end' => '13:00'],
        // ],
    ];
    
    /**
     * 既存の勤怠データを削除するかどうか
     * true: 指定期間の既存データを削除してから作成
     * false: 既存データがあってもそのまま作成（重複の可能性あり）
     */
    private $deleteExisting = true;

    // ============================================
    // 以下は変更不要
    // ============================================

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ユーザーの存在確認
        $user = User::find($this->userId);
        if (!$user) {
            $this->command->error("ユーザーID {$this->userId} が見つかりません。");
            return;
        }

        // 日付範囲の検証
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        
        if ($start->greaterThan($end)) {
            $this->command->error("開始日が終了日より後になっています。");
            return;
        }

        $days = $start->diffInDays($end) + 1;
        
        // 配列の要素数チェック
        if (count($this->clockInTimes) < $days) {
            $this->command->error("clockInTimesの要素数が不足しています。{$days}日分必要です。");
            return;
        }
        
        if (count($this->clockOutTimes) < $days) {
            $this->command->error("clockOutTimesの要素数が不足しています。{$days}日分必要です。");
            return;
        }
        
        if (count($this->breakTimes) < $days) {
            $this->command->error("breakTimesの要素数が不足しています。{$days}日分必要です。");
            return;
        }

        // 既存データの削除
        if ($this->deleteExisting) {
            $deleted = Attendance::where('user_id', $this->userId)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->delete();
            
            if ($deleted > 0) {
                $this->command->info("既存の勤怠データ {$deleted} 件を削除しました。");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("勤怠データを作成します");
        $this->command->info("ユーザー: {$user->name} (ID: {$this->userId})");
        $this->command->info("期間: {$this->startDate} ～ {$this->endDate} ({$days}日間)");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $createdCount = 0;

        // 各日付の勤怠データを作成
        $currentDate = $start->copy();
        $dayIndex = 0;

        while ($currentDate->lte($end)) {
            $dateStr = $currentDate->format('Y-m-d');
            $clockInTimeStr = $this->clockInTimes[$dayIndex];
            $clockOutTimeStr = $this->clockOutTimes[$dayIndex];
            $breaks = $this->breakTimes[$dayIndex];

            // 出勤時間の作成
            $clockInTime = Carbon::parse($dateStr . ' ' . $clockInTimeStr);

            // 退勤時間の作成（日をまたぐかどうかを判定）
            $clockOutTime = Carbon::parse($dateStr . ' ' . $clockOutTimeStr);
            
            // 退勤時間が出勤時間より小さい場合は翌日として扱う
            if ($clockOutTime->format('H:i') < $clockInTime->format('H:i')) {
                $clockOutTime = $clockOutTime->copy()->addDay();
            }

            // 勤怠レコードを作成
            $attendance = Attendance::create([
                'user_id' => $this->userId,
                'date' => $dateStr,
                'clock_in_time' => $clockInTime,
                'clock_out_time' => $clockOutTime,
                'status' => Attendance::STATUS_FINISHED,
            ]);

            // 休憩時間を作成
            $breakCount = 0;
            foreach ($breaks as $break) {
                if (empty($break['start']) || empty($break['end'])) {
                    continue;
                }

                $breakStart = Carbon::parse($dateStr . ' ' . $break['start']);
                $breakEnd = Carbon::parse($dateStr . ' ' . $break['end']);

                // 休憩終了時間が休憩開始時間より小さい場合は翌日として扱う
                if ($breakEnd->format('H:i') < $breakStart->format('H:i')) {
                    $breakEnd = $breakEnd->copy()->addDay();
                }

                // 休憩時間が出勤時間より前、または退勤時間より後でないかチェック
                if ($breakStart->lessThan($clockInTime) || $breakEnd->greaterThan($clockOutTime)) {
                    $this->command->warn("  ⚠ 休憩時間が勤務時間外のためスキップ: {$break['start']} - {$break['end']}");
                    continue;
                }

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start_time' => $breakStart,
                    'break_end_time' => $breakEnd,
                ]);

                $breakCount++;
            }

            $workMinutes = $clockOutTime->diffInMinutes($clockInTime);
            $workHours = floor($workMinutes / 60);
            $workMinutesRemainder = $workMinutes % 60;

            $this->command->line("✓ {$dateStr}: {$clockInTimeStr} - {$clockOutTimeStr} (勤務: {$workHours}:" . str_pad($workMinutesRemainder, 2, '0', STR_PAD_LEFT) . ", 休憩: {$breakCount}回)");

            $createdCount++;
            $currentDate->addDay();
            $dayIndex++;
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("勤怠データ {$createdCount} 件を作成しました。");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}



