<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class TwentyFiveHourAttendanceSeeder extends Seeder
{
    /**
     * 25時間連続勤務の勤怠レコードを作成（開発用テストデータ）
     *
     * @return void
     */
    public function run()
    {
        // 2025年11月1日を基準日として設定
        $baseDate = Carbon::create(2025, 11, 1, 0, 0, 0);
        
        $user = User::find(1);

        if (!$user) {
            $this->command->error('user_id=1のユーザーが見つかりません。');
            return;
        }

        // 11/1 23:00 から 11/3 00:00 まで（25時間）
        $clockInTime = $baseDate->copy()->setTime(23, 0, 0);
        $clockOutTime = $baseDate->copy()->addDays(2)->setTime(0, 0, 0);

        // 既に同じ日付のレコードが存在する場合は削除
        Attendance::where('user_id', $user->id)->where('date', $baseDate->format('Y-m-d'))->delete();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $baseDate->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $workMinutes = $clockOutTime->diffInMinutes($clockInTime);
        $workHours = floor($workMinutes / 60);
        $workMinutesRemainder = $workMinutes % 60;

        $this->command->info('25時間連続勤務の勤怠レコードを作成しました。');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line("ID: {$attendance->id}");
        $this->command->line("ユーザー: {$user->name} (user_id: {$user->id})");
        $this->command->line("日付: {$attendance->date}");
        $this->command->line("出勤: {$attendance->clock_in_time}");
        $this->command->line("退勤: {$attendance->clock_out_time}");
        $this->command->line("勤務時間: {$workMinutes}分（{$workHours}:" . str_pad($workMinutesRemainder, 2, '0', STR_PAD_LEFT) . "）");
        $this->command->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}

