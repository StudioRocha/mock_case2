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

class TestId7BreakFeatureTest extends TestCase
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
        
        // CSRFミドルウェアを無効化（テスト環境では不要）
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        // セッションをクリア（2回目以降のテストでセッションが残らないようにする）
        $this->session([]);
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
     * テストID: 7-1
     * 休憩ボタンが正しく機能する
     */
    public function test_break_button_functionality()
    {
        // 1. ステータスが出勤中のユーザーにログインする
        $user = $this->createTestUser();
        $this->createWorkingAttendance($user);

        // 2. 画面に「休憩入」ボタンが表示されていることを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');
        
        // ステータスが出勤中であることを確認
        $status = $response->viewData('status');
        $this->assertEquals(Attendance::STATUS_WORKING, $status);
        
        // 「休憩入」ボタンが表示されていることを確認
        $response->assertSee('休憩入', false);

        // 3. 休憩の処理を行う
        $breakStartResponse = $this->actingAs($user)->post('/attendance/break-start');
        $breakStartResponse->assertRedirect(route('attendance'));

        // 処理後に画面上に表示されるステータスが「休憩中」になることを確認
        $afterResponse = $this->actingAs($user)->get('/attendance');
        $statusLabel = $afterResponse->viewData('statusLabel');
        $this->assertEquals('休憩中', $statusLabel, '画面上に表示されているステータスが「休憩中」になる');
        
        // データベースに休憩レコードが作成されていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(Attendance::STATUS_BREAK, $attendance->status);
        
        $breakTime = BreakTime::where('attendance_id', $attendance->id)
            ->whereNotNull('break_start_time')
            ->whereNull('break_end_time')
            ->first();
        $this->assertNotNull($breakTime, '休憩レコードが作成されている');
    }

    /**
     * テストID: 7-2
     * 休憩は一日に何回でもできる
     */
    public function test_break_can_be_done_multiple_times_per_day()
    {
        // 1. ステータスが出勤中であるユーザーにログインする
        $user = $this->createTestUser();
        $this->createWorkingAttendance($user);

        // 2. 休憩入と休憩戻の処理を行う
        // 1回目の休憩入
        $breakStartResponse1 = $this->actingAs($user)->post('/attendance/break-start');
        $breakStartResponse1->assertRedirect(route('attendance'));

        // 1回目の休憩戻
        $breakEndResponse1 = $this->actingAs($user)->post('/attendance/break-end');
        $breakEndResponse1->assertRedirect(route('attendance'));

        // 3. 「休憩入」ボタンが表示されることを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');
        
        // ステータスが出勤中に戻っていることを確認
        $status = $response->viewData('status');
        $this->assertEquals(Attendance::STATUS_WORKING, $status, 'ステータスが出勤中に戻っている');
        
        // 「休憩入」ボタンが表示されていることを確認
        $response->assertSee('休憩入', false);
        
        // データベースに休憩レコードが2つ（開始時刻と終了時刻が記録されたもの）存在することを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $breakTimes = BreakTime::where('attendance_id', $attendance->id)
            ->whereNotNull('break_start_time')
            ->whereNotNull('break_end_time')
            ->get();
        $this->assertGreaterThanOrEqual(1, $breakTimes->count(), '休憩レコードが記録されている');
    }

    /**
     * テストID: 7-3
     * 休憩戻ボタンが正しく機能する
     */
    public function test_break_end_button_functionality()
    {
        // 1. ステータスが出勤中であるユーザーにログインする
        $user = $this->createTestUser();
        $attendance = $this->createWorkingAttendance($user);

        // 2. 休憩入の処理を行う
        $breakStartResponse = $this->actingAs($user)->post('/attendance/break-start');
        $breakStartResponse->assertRedirect(route('attendance'));

        // 休憩中の状態を確認
        $attendance->refresh();
        $this->assertEquals(Attendance::STATUS_BREAK, $attendance->status);

        // 3. 休憩戻の処理を行う
        $breakEndResponse = $this->actingAs($user)->post('/attendance/break-end');
        $breakEndResponse->assertRedirect(route('attendance'));

        // 処理後に画面上に表示されるステータスが「出勤中」になることを確認
        $afterResponse = $this->actingAs($user)->get('/attendance');
        $statusLabel = $afterResponse->viewData('statusLabel');
        $this->assertEquals('出勤中', $statusLabel, '画面上に表示されているステータスが「出勤中」になる');
        
        // データベースの休憩レコードに終了時刻が記録されていることを確認
        $attendance->refresh();
        $this->assertEquals(Attendance::STATUS_WORKING, $attendance->status);
        
        $breakTime = BreakTime::where('attendance_id', $attendance->id)
            ->whereNotNull('break_start_time')
            ->whereNotNull('break_end_time')
            ->first();
        $this->assertNotNull($breakTime, '休憩レコードに終了時刻が記録されている');
    }

    /**
     * テストID: 7-4
     * 休憩戻は一日に何回でもできる
     */
    public function test_break_end_can_be_done_multiple_times_per_day()
    {
        // 1. ステータスが出勤中であるユーザーにログインする
        $user = $this->createTestUser();
        $this->createWorkingAttendance($user);

        // 2. 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
        // 1回目の休憩入
        $breakStartResponse1 = $this->actingAs($user)->post('/attendance/break-start');
        $breakStartResponse1->assertRedirect(route('attendance'));

        // 1回目の休憩戻
        $breakEndResponse1 = $this->actingAs($user)->post('/attendance/break-end');
        $breakEndResponse1->assertRedirect(route('attendance'));

        // 2回目の休憩入
        $breakStartResponse2 = $this->actingAs($user)->post('/attendance/break-start');
        $breakStartResponse2->assertRedirect(route('attendance'));

        // 3. 「休憩戻」ボタンが表示されることを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');
        
        // ステータスが休憩中であることを確認
        $status = $response->viewData('status');
        $this->assertEquals(Attendance::STATUS_BREAK, $status, 'ステータスが休憩中である');
        
        // 「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('休憩戻', false);
        
        // データベースに複数の休憩レコードが存在することを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $breakTimes = BreakTime::where('attendance_id', $attendance->id)->get();
        $this->assertGreaterThanOrEqual(2, $breakTimes->count(), '複数の休憩レコードが記録されている');
    }

    /**
     * テストID: 7-5
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_time_displayed_in_list()
    {
        // 1. ステータスが勤務中のユーザーにログインする
        $user = $this->createTestUser();
        $attendance = $this->createWorkingAttendance($user);

        // 2. 休憩入と休憩戻の処理を行う
        // 休憩入
        $breakStartResponse = $this->actingAs($user)->post('/attendance/break-start');
        $breakStartResponse->assertRedirect(route('attendance'));

        // 少し時間を進める（休憩時間を確保）
        $this->travel(30)->minutes();

        // 休憩戻
        $breakEndResponse = $this->actingAs($user)->post('/attendance/break-end');
        $breakEndResponse->assertRedirect(route('attendance'));

        // 退勤処理を行って、退勤済みにする（休憩時間は退勤済みの場合のみ表示される）
        $attendance->update([
            'clock_out_time' => Carbon::now(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 3. 勤怠一覧画面から休憩の日付を確認する
        $now = Carbon::now();
        $response = $this->actingAs($user)->get('/attendance/list/' . $now->year . '/' . $now->month);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');
        
        // 勤怠一覧画面に休憩時間が表示されていることを確認
        $attendances = $response->viewData('attendances');
        $todayAttendance = $attendances->firstWhere('date', Carbon::today());
        
        $this->assertNotNull($todayAttendance, '今日の勤怠レコードが存在する');
        $this->assertNotNull($todayAttendance->total_break_time, '休憩時間が計算されている');
        $this->assertNotEmpty($todayAttendance->total_break_time, '休憩時間が表示されている');
    }
}

