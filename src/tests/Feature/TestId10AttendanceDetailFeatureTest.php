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

class TestId10AttendanceDetailFeatureTest extends TestCase
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
     * テストID: 10-1
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_name_displays_logged_in_user_name()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        
        // 勤怠レコードを作成
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');

        // 3. 名前欄を確認する
        $attendanceData = $response->viewData('attendance');
        $this->assertEquals($user->name, $attendanceData->user->name, '名前がログインユーザーの名前になっている');
        
        // ビューにユーザー名が表示されていることを確認
        $response->assertSee($user->name, false);
    }

    /**
     * テストID: 10-2
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_date_displays_selected_date()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        
        // 特定の日付で勤怠レコードを作成
        $selectedDate = Carbon::create(2024, 6, 15);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $selectedDate,
            'clock_in_time' => $selectedDate->copy()->setTime(9, 0, 0),
            'clock_out_time' => $selectedDate->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');

        // 3. 日付欄を確認する
        $attendanceData = $response->viewData('attendance');
        $this->assertEquals($selectedDate->format('Y-m-d'), $attendanceData->date->format('Y-m-d'), '日付が選択した日付になっている');
        
        // ビューに日付が表示されていることを確認（Y年 n月j日形式）
        $expectedDateYear = $selectedDate->format('Y年');
        $expectedDateMonthDay = $selectedDate->format('n月j日');
        $response->assertSee($expectedDateYear, false);
        $response->assertSee($expectedDateMonthDay, false);
    }

    /**
     * テストID: 10-3
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_clock_in_out_time_matches_user_stamp()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        
        // 特定の出勤・退勤時刻で勤怠レコードを作成
        $today = Carbon::today();
        $clockInTime = $today->copy()->setTime(9, 15, 0);
        $clockOutTime = $today->copy()->setTime(18, 30, 0);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');

        // 3. 出勤・退勤欄を確認する
        $displayClockInTime = $response->viewData('displayClockInTime');
        $displayClockOutTime = $response->viewData('displayClockOutTime');
        
        $this->assertEquals($clockInTime->format('H:i'), $displayClockInTime, '「出勤・退勤」にて記されている出勤時間がログインユーザーの打刻と一致している');
        $this->assertEquals($clockOutTime->format('H:i'), $displayClockOutTime, '「出勤・退勤」にて記されている退勤時間がログインユーザーの打刻と一致している');
        
        // ビューに出勤・退勤時刻が表示されていることを確認
        $response->assertSee($clockInTime->format('H:i'), false);
        $response->assertSee($clockOutTime->format('H:i'), false);
    }

    /**
     * テストID: 10-4
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_break_time_matches_user_stamp()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        
        // 特定の休憩時刻で勤怠レコードを作成
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        // 休憩レコードを作成
        $breakStartTime1 = $today->copy()->setTime(12, 0, 0);
        $breakEndTime1 = $today->copy()->setTime(13, 0, 0);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $breakStartTime1,
            'break_end_time' => $breakEndTime1,
        ]);
        
        $breakStartTime2 = $today->copy()->setTime(14, 30, 0);
        $breakEndTime2 = $today->copy()->setTime(15, 15, 0);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $breakStartTime2,
            'break_end_time' => $breakEndTime2,
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');

        // 3. 休憩欄を確認する
        $breakDetails = $response->viewData('breakDetails');
        
        // 有効な休憩レコードのみを確認（最後の空白休憩を除く）
        $validBreaks = array_filter($breakDetails, function ($break) {
            return !empty($break['start_time']) && !empty($break['end_time']);
        });
        
        $this->assertCount(2, $validBreaks, '2つの休憩レコードが表示されている');
        
        // 1つ目の休憩時間を確認
        $firstBreak = array_values($validBreaks)[0];
        $this->assertEquals($breakStartTime1->format('H:i'), $firstBreak['start_time'], '1つ目の休憩開始時間がログインユーザーの打刻と一致している');
        $this->assertEquals($breakEndTime1->format('H:i'), $firstBreak['end_time'], '1つ目の休憩終了時間がログインユーザーの打刻と一致している');
        
        // 2つ目の休憩時間を確認
        $secondBreak = array_values($validBreaks)[1];
        $this->assertEquals($breakStartTime2->format('H:i'), $secondBreak['start_time'], '2つ目の休憩開始時間がログインユーザーの打刻と一致している');
        $this->assertEquals($breakEndTime2->format('H:i'), $secondBreak['end_time'], '2つ目の休憩終了時間がログインユーザーの打刻と一致している');
        
        // ビューに休憩時刻が表示されていることを確認
        $response->assertSee($breakStartTime1->format('H:i'), false);
        $response->assertSee($breakEndTime1->format('H:i'), false);
        $response->assertSee($breakStartTime2->format('H:i'), false);
        $response->assertSee($breakEndTime2->format('H:i'), false);
    }
}

