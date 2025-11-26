<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class OvernightAttendanceSeeder extends Seeder
{
    /**
     * 日をまたぐ勤怠レコードを作成（開発用テストデータ）
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $user = User::where('role', User::ROLE_USER)->first();

        if (!$user) {
            $this->command->error('一般ユーザーが見つかりません。');
            return;
        }

        $clockInTime = $now->copy()->setTime(23, 0, 0);
        $clockOutTime = $now->copy()->addDay()->setTime(2, 0, 0);

        // 既に今日のレコードが存在する場合は削除
        Attendance::where('user_id', $user->id)->where('date', $today)->delete();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $workMinutes = $clockOutTime->diffInMinutes($clockInTime);
        $workHours = floor($workMinutes / 60);
        $workMinutesRemainder = $workMinutes % 60;

        $this->command->info('日をまたぐ勤怠レコードを作成しました。');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line("ID: {$attendance->id}");
        $this->command->line("ユーザー: {$user->name}");
        $this->command->line("日付: {$attendance->date}");
        $this->command->line("出勤: {$attendance->clock_in_time}");
        $this->command->line("退勤: {$attendance->clock_out_time}");
        $this->command->line("勤務時間: {$workMinutes}分（{$workHours}:" . str_pad($workMinutesRemainder, 2, '0', STR_PAD_LEFT) . "）");
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}

