<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId13AdminAttendanceDetailFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト前のセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // メール送信をモック化
        Mail::fake();
    }

    /**
     * テスト用の管理者ユーザーを作成（メール認証済み）
     */
    private function createTestAdminUser(): User
    {
        $adminRole = Role::where('name', Role::NAME_ADMIN)->firstOrFail();
        
        $user = User::create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
        ]);
        
        // メール認証済みにする
        $user->markEmailAsVerified();
        
        return $user;
    }

    /**
     * テスト用の一般ユーザーを作成（メール認証済み）
     */
    private function createTestUser(): User
    {
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $userRole->id,
        ]);
        
        // メール認証済みにする
        $user->markEmailAsVerified();
        
        return $user;
    }

    /**
     * 退勤済みの勤怠レコードを作成
     */
    private function createFinishedAttendance(User $user): Attendance
    {
        $today = Carbon::today();
        return Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
    }

    /**
     * テストID: 13-1
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_detail_displays_selected_attendance_data()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        
        // 特定の日付と時刻で勤怠レコードを作成
        $selectedDate = Carbon::create(2024, 6, 15);
        $clockInTime = $selectedDate->copy()->setTime(9, 15, 0);
        $clockOutTime = $selectedDate->copy()->setTime(18, 30, 0);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $selectedDate,
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        // 休憩レコードを作成
        $breakStartTime = $selectedDate->copy()->setTime(12, 0, 0);
        $breakEndTime = $selectedDate->copy()->setTime(13, 0, 0);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $breakStartTime,
            'break_end_time' => $breakEndTime,
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance.detail');

        // 詳細画面の内容が選択した情報と一致することを確認
        $attendanceData = $response->viewData('attendance');
        $this->assertEquals($attendance->id, $attendanceData->id, '選択した勤怠レコードが表示されている');
        $this->assertEquals($selectedDate->format('Y-m-d'), $attendanceData->date->format('Y-m-d'), '選択した日付が表示されている');
        
        $displayClockInTime = $response->viewData('displayClockInTime');
        $displayClockOutTime = $response->viewData('displayClockOutTime');
        $this->assertEquals($clockInTime->format('H:i'), $displayClockInTime, '選択した出勤時刻が表示されている');
        $this->assertEquals($clockOutTime->format('H:i'), $displayClockOutTime, '選択した退勤時刻が表示されている');
        
        // 休憩時間も確認
        $breakDetails = $response->viewData('breakDetails');
        $validBreaks = array_filter($breakDetails, function ($break) {
            return !empty($break['start_time']) && !empty($break['end_time']);
        });
        $this->assertGreaterThanOrEqual(1, count($validBreaks), '選択した休憩時間が表示されている');
    }

    /**
     * テストID: 13-2
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_time_after_clock_out_time_shows_error()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 出勤時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_time' => '18:00', // 退勤時間（17:00）より後
            'clock_out_time' => '17:00',
            'break_start_times' => [],
            'break_end_times' => [],
            'note' => 'テスト備考',
        ]);

        // バリデーションエラーが表示されることを確認
        $response->assertSessionHasErrors();
        
        // エラーメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        // 実装では「出勤時間もしくは退勤時間が不適切な値です」というメッセージが表示される
        $this->assertTrue($errors->has('clock_time'), '出勤時間もしくは退勤時間が不適切な値であることを示すエラーが表示される');
    }

    /**
     * テストID: 13-3
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_time_after_clock_out_time_shows_error()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 休憩開始時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_time' => '09:00',
            'clock_out_time' => '17:00',
            'break_start_times' => ['18:00'], // 退勤時間（17:00）より後
            'break_end_times' => ['18:30'],
            'note' => 'テスト備考',
        ]);

        // バリデーションエラーが表示されることを確認
        $response->assertSessionHasErrors();
        
        // エラーメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('break_start_times.0'), '休憩時間が不適切な値であることを示すエラーが表示される');
        $this->assertContains('休憩時間が不適切な値です', $errors->get('break_start_times.0'));
    }

    /**
     * テストID: 13-4
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_time_after_clock_out_time_shows_error()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 休憩終了時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_time' => '09:00',
            'clock_out_time' => '17:00',
            'break_start_times' => ['12:00'],
            'break_end_times' => ['18:00'], // 退勤時間（17:00）より後
            'note' => 'テスト備考',
        ]);

        // バリデーションエラーが表示されることを確認
        $response->assertSessionHasErrors();
        
        // エラーメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('break_end_times.0'), '休憩時間もしくは退勤時間が不適切な値であることを示すエラーが表示される');
        $this->assertContains('休憩時間もしくは退勤時間が不適切な値です', $errors->get('break_end_times.0'));
    }

    /**
     * テストID: 13-5
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_empty_note_shows_error()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 備考欄を未入力のまま保存処理をする
        $response = $this->actingAs($admin)->put('/admin/attendance/' . $attendance->id, [
            'clock_in_time' => '09:00',
            'clock_out_time' => '17:00',
            'break_start_times' => [],
            'break_end_times' => [],
            'note' => '', // 未入力
        ]);

        // バリデーションエラーが表示されることを確認
        $response->assertSessionHasErrors('note');
        
        // エラーメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertContains('備考を記入してください', $errors->get('note'));
    }
}



