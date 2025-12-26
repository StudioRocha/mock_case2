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

class TestId5AttendanceStatusFeatureTest extends TestCase
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
     * テストID: 5-1
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_off_duty_display()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = $this->createTestUser();
        // 勤怠レコードは作成しない（勤務外状態）

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');

        // 3. 画面に表示されているステータスを確認する
        $statusLabel = $response->viewData('statusLabel');
        $this->assertEquals('勤務外', $statusLabel, '画面上に表示されているステータスが「勤務外」となる');
    }

    /**
     * テストID: 5-2
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_working_display()
    {
        // 1. ステータスが出勤中のユーザーにログインする
        $user = $this->createTestUser();
        
        // 出勤中の勤怠レコードを作成
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => null,
            'status' => Attendance::STATUS_WORKING,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');

        // 3. 画面に表示されているステータスを確認する
        $statusLabel = $response->viewData('statusLabel');
        $this->assertEquals('出勤中', $statusLabel, '画面上に表示されているステータスが「出勤中」となる');
    }

    /**
     * テストID: 5-3
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_break_display()
    {
        // 1. ステータスが休憩中のユーザーにログインする
        $user = $this->createTestUser();
        
        // 休憩中の勤怠レコードを作成
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => null,
            'status' => Attendance::STATUS_BREAK,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');

        // 3. 画面に表示されているステータスを確認する
        $statusLabel = $response->viewData('statusLabel');
        $this->assertEquals('休憩中', $statusLabel, '画面上に表示されているステータスが「休憩中」となる');
    }

    /**
     * テストID: 5-4
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_finished_display()
    {
        // 1. ステータスが退勤済のユーザーにログインする
        $user = $this->createTestUser();
        
        // 退勤済の勤怠レコードを作成
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');

        // 3. 画面に表示されているステータスを確認する
        $statusLabel = $response->viewData('statusLabel');
        $this->assertEquals('退勤済', $statusLabel, '画面上に表示されているステータスが「退勤済」となる');
    }
}

