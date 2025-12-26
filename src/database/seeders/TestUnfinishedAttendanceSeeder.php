<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use Carbon\Carbon;

/**
 * Featureテスト用：退勤ボタンを押していない勤怠データを作成
 * 
 * 使用方法:
 * php artisan db:seed --class=TestUnfinishedAttendanceSeeder
 */
class TestUnfinishedAttendanceSeeder extends Seeder
{
    /**
     * 退勤ボタンを押していない勤怠データを作成
     *
     * @return void
     */
    public function run()
    {
        // 一般ユーザーのロールを取得
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();

        // 一般ユーザーを1人取得（最初の一般ユーザー）
        $user = User::where('role_id', $userRole->id)->first();

        if (!$user) {
            $this->command->error('一般ユーザーが見つかりません。先にAdminUserSeederを実行してください。');
            return;
        }

        // 昨日の日付を取得
        $yesterday = Carbon::yesterday();
        $yesterdayDate = $yesterday->format('Y-m-d');

        // 既に昨日の勤怠データが存在するかチェック
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $yesterdayDate)
            ->first();

        if ($existingAttendance) {
            $this->command->warn("既に昨日（{$yesterdayDate}）の勤怠データが存在します。");
            $this->command->warn("ユーザー: {$user->name} (ID: {$user->id})");
            return;
        }

        // 昨日の22:00に出勤したが退勤していないデータを作成
        // 出勤ボタンを押すと自動的にSTATUS_WORKING（出勤中）になる
        $clockInTime = $yesterday->copy()->setTime(22, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $yesterdayDate,
            'clock_in_time' => $clockInTime,
            'clock_out_time' => null, // 退勤ボタンを押していない
            'status' => Attendance::STATUS_WORKING, // 出勤時は自動で出勤中になる
        ]);

        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('退勤ボタンを押していない勤怠データを作成しました。');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("ユーザー: {$user->name} (ID: {$user->id})");
        $this->command->info("日付: {$yesterdayDate}");
        $this->command->info("出勤時刻: {$clockInTime->format('Y-m-d H:i:s')}");
        $this->command->info("退勤時刻: NULL（未退勤）");
        $this->command->info("ステータス: 出勤中 (STATUS_WORKING)");
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}

