<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceDummyDataSeeder extends Seeder
{
    /**
     * 勤怠ダミーデータを作成
     *
     * @return void
     */
    public function run()
    {
        // 一般ユーザーを作成
        $users = [
            ['name' => '山田太郎', 'email' => 'yamada@example.com'],
            ['name' => '佐藤花子', 'email' => 'sato@example.com'],
            ['name' => '鈴木一郎', 'email' => 'suzuki@example.com'],
            ['name' => '田中次郎', 'email' => 'tanaka@example.com'],
            ['name' => '伊藤三郎', 'email' => 'ito@example.com'],
            ['name' => '渡辺四郎', 'email' => 'watanabe@example.com'],
        ];

        // 一般ユーザーのロールを取得
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();

        $createdUsers = [];
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $userRole->id,
                ]
            );
            $createdUsers[] = $user;
        }

        $this->command->info('一般ユーザーを作成しました。');

        // シーディング実行時の前日から1ヶ月分（30日分）のデータを作成
        $today = Carbon::today();
        $dates = [];
        // 前日（昨日）から30日分の日付を生成
        for ($i = 1; $i <= 30; $i++) {
            $dates[] = $today->copy()->subDays($i);
        }

        // 利用可能なパターンメソッドのリスト
        $patterns = [
            'createNormalAttendance',
            'createOvernightAttendance',
            'createMultipleBreaksAttendance',
            'createOvernightBreakAttendance',
            'createLongHoursAttendance',
            'createShortHoursAttendance',
            'createOvernightMultipleBreaksAttendance',
            'createNightShiftAttendance',
            'createEarlyMorningAttendance',
        ];

        // 各日付ごとに、1人を除いた全ユーザーに勤怠データを登録（必ず1人は未登録）
        foreach ($dates as $dateIndex => $date) {
            // 各日付ごとに、未登録にするユーザーをランダムに選択
            $excludedUserIndex = array_rand($createdUsers);
            
            foreach ($createdUsers as $userIndex => $user) {
                // 除外されたユーザーはスキップ（未登録のまま）
                if ($userIndex === $excludedUserIndex) {
                    $this->command->line("⊘ 未登録: {$user->name} - {$date->format('Y-m-d')}");
                    continue;
                }
                
                // ランダムにパターンを選択
                $randomPattern = $patterns[array_rand($patterns)];
                
                // 選択されたパターンのメソッドを呼び出し
                $this->$randomPattern($user, $date->copy());
            }
        }

        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('すべてのダミーデータの作成が完了しました。');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    /**
     * パターン1: 通常の勤務（9:00-18:00、休憩1時間）
     */
    private function createNormalAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(9, 0, 0);
        $clockOutTime = $date->copy()->setTime(18, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩: 12:00-13:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(12, 0, 0),
            'break_end_time' => $date->copy()->setTime(13, 0, 0),
        ]);

        $this->command->line("✓ 通常勤務: {$user->name} - {$date->format('Y-m-d')} (9:00-18:00, 休憩1時間)");
    }

    /**
     * パターン2: 日付跨ぎ勤務（23:00-02:00、3時間）
     */
    private function createOvernightAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(23, 0, 0);
        $clockOutTime = $date->copy()->addDay()->setTime(2, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $this->command->line("✓ 日付跨ぎ勤務: {$user->name} - {$date->format('Y-m-d')} (23:00-翌日02:00)");
    }

    /**
     * パターン10: 早朝勤務（5:00-14:00、休憩1時間）- 同じ日付内
     */
    private function createEarlyMorningAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(5, 0, 0);
        $clockOutTime = $date->copy()->setTime(14, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩: 9:00-10:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(9, 0, 0),
            'break_end_time' => $date->copy()->setTime(10, 0, 0),
        ]);

        $this->command->line("✓ 早朝勤務: {$user->name} - {$date->format('Y-m-d')} (5:00-14:00, 休憩1時間)");
    }

    /**
     * パターン4: 複数の休憩時間（9:00-20:00、休憩3回）
     */
    private function createMultipleBreaksAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(9, 0, 0);
        $clockOutTime = $date->copy()->setTime(20, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩1: 12:00-13:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(12, 0, 0),
            'break_end_time' => $date->copy()->setTime(13, 0, 0),
        ]);

        // 休憩2: 15:00-15:30
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(15, 0, 0),
            'break_end_time' => $date->copy()->setTime(15, 30, 0),
        ]);

        // 休憩3: 17:00-17:15
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(17, 0, 0),
            'break_end_time' => $date->copy()->setTime(17, 15, 0),
        ]);

        $this->command->line("✓ 複数休憩: {$user->name} - {$date->format('Y-m-d')} (9:00-20:00, 休憩3回)");
    }

    /**
     * パターン5: 休憩時間の日跨ぎ（23:00-翌日08:00、休憩が日跨ぎ）
     */
    private function createOvernightBreakAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(23, 0, 0);
        $clockOutTime = $date->copy()->addDay()->setTime(8, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩: 翌日01:00-02:00（日跨ぎ）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->addDay()->setTime(1, 0, 0),
            'break_end_time' => $date->copy()->addDay()->setTime(2, 0, 0),
        ]);

        $this->command->line("✓ 休憩日跨ぎ: {$user->name} - {$date->format('Y-m-d')} (23:00-翌日08:00, 休憩が日跨ぎ)");
    }

    /**
     * パターン6: 長時間勤務（8:00-22:00、休憩2時間）
     */
    private function createLongHoursAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(8, 0, 0);
        $clockOutTime = $date->copy()->setTime(22, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩1: 12:00-13:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(12, 0, 0),
            'break_end_time' => $date->copy()->setTime(13, 0, 0),
        ]);

        // 休憩2: 17:00-18:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(17, 0, 0),
            'break_end_time' => $date->copy()->setTime(18, 0, 0),
        ]);

        $this->command->line("✓ 長時間勤務: {$user->name} - {$date->format('Y-m-d')} (8:00-22:00, 休憩2時間)");
    }

    /**
     * パターン7: 短時間勤務（10:00-15:00、休憩30分）
     */
    private function createShortHoursAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(10, 0, 0);
        $clockOutTime = $date->copy()->setTime(15, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩: 12:30-13:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(12, 30, 0),
            'break_end_time' => $date->copy()->setTime(13, 0, 0),
        ]);

        $this->command->line("✓ 短時間勤務: {$user->name} - {$date->format('Y-m-d')} (10:00-15:00, 休憩30分)");
    }

    /**
     * パターン8: 日跨ぎ+複数休憩（22:00-翌日06:00、休憩2回）
     */
    private function createOvernightMultipleBreaksAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(22, 0, 0);
        $clockOutTime = $date->copy()->addDay()->setTime(6, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩1: 当日23:30-翌日00:30（日跨ぎ）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->setTime(23, 30, 0),
            'break_end_time' => $date->copy()->addDay()->setTime(0, 30, 0),
        ]);

        // 休憩2: 翌日03:00-04:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->addDay()->setTime(3, 0, 0),
            'break_end_time' => $date->copy()->addDay()->setTime(4, 0, 0),
        ]);

        $this->command->line("✓ 日跨ぎ+複数休憩: {$user->name} - {$date->format('Y-m-d')} (22:00-翌日06:00, 休憩2回)");
    }

    /**
     * パターン9: 深夜勤務（20:00-翌日05:00、休憩1時間）
     */
    private function createNightShiftAttendance($user, $date)
    {
        $clockInTime = $date->copy()->setTime(20, 0, 0);
        $clockOutTime = $date->copy()->addDay()->setTime(5, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 休憩: 翌日01:00-02:00
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $date->copy()->addDay()->setTime(1, 0, 0),
            'break_end_time' => $date->copy()->addDay()->setTime(2, 0, 0),
        ]);

        $this->command->line("✓ 深夜勤務: {$user->name} - {$date->format('Y-m-d')} (20:00-翌日05:00, 休憩1時間)");
    }
}

