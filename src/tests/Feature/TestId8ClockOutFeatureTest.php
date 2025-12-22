<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId8ClockOutFeatureTest extends TestCase
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
     * 出勤中の勤怠レコードを作成
     */
    private function createWorkingAttendance(User $user): Attendance
    {
        $today = Carbon::today();
        return Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => null,
            'status' => Attendance::STATUS_WORKING,
        ]);
    }

    /**
     * テストID: 8-1
     * 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_functionality()
    {
        // 1. ステータスが勤務中のユーザーにログインする
        $user = $this->createTestUser();
        $this->createWorkingAttendance($user);

        // 2. 画面に「退勤」ボタンが表示されていることを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');
        
        // ステータスが出勤中であることを確認
        $status = $response->viewData('status');
        $this->assertEquals(Attendance::STATUS_WORKING, $status);
        
        // 「退勤」ボタンが表示されていることを確認
        $response->assertSee('退勤', false);

        // 3. 退勤の処理を行う
        $clockOutResponse = $this->actingAs($user)->post('/attendance/clock-out');
        $clockOutResponse->assertRedirect(route('attendance'));

        // 処理後に画面上に表示されるステータスが「退勤済」になることを確認
        $afterResponse = $this->actingAs($user)->get('/attendance');
        $statusLabel = $afterResponse->viewData('statusLabel');
        $this->assertEquals('退勤済', $statusLabel, '画面上に表示されているステータスが「退勤済」になる');
        
        // データベースに退勤時刻が記録されていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(Attendance::STATUS_FINISHED, $attendance->status);
        $this->assertNotNull($attendance->clock_out_time, '退勤時刻が記録されている');
    }

    /**
     * テストID: 8-2
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_displayed_in_list()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = $this->createTestUser();
        
        // 今日の日付を保存（travelで時間を進める前に）
        $today = Carbon::today();

        // 2. 出勤と退勤の処理を行う
        // 出勤処理
        $clockInResponse = $this->actingAs($user)->post('/attendance/clock-in');
        $clockInResponse->assertRedirect(route('attendance'));

        // 出勤時刻を記録
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        $clockInTime = $attendance->clock_in_time;

        // 少し時間を進める
        $this->travel(8)->hours();

        // 退勤処理
        $clockOutResponse = $this->actingAs($user)->post('/attendance/clock-out');
        $clockOutResponse->assertRedirect(route('attendance'));

        // 退勤時刻を記録
        $attendance->refresh();
        $clockOutTime = $attendance->clock_out_time;

        // 3. 勤怠一覧画面から退勤の日付を確認する
        // 今日の年月でリクエスト（travelで時間を進めても、日付は変わらない前提）
        $response = $this->actingAs($user)->get('/attendance/list/' . $today->year . '/' . $today->month);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');
        
        // 勤怠一覧画面に退勤時刻が正確に記録されていることを確認
        $attendances = $response->viewData('attendances');
        $todayAttendance = $attendances->first(function ($attendance) use ($today) {
            return $attendance->date->isSameDay($today);
        });
        
        $this->assertNotNull($todayAttendance, '今日の勤怠レコードが存在する');
        $this->assertNotNull($todayAttendance->formatted_clock_out_time, '退勤時刻がフォーマットされている');
        $this->assertEquals(
            $clockOutTime->format('H:i'),
            $todayAttendance->formatted_clock_out_time,
            '勤怠一覧画面に退勤時刻が正確に記録されている'
        );
    }
}

