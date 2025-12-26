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

class TestId6ClockInFeatureTest extends TestCase
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
     * テストID: 6-1
     * 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_functionality()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = $this->createTestUser();

        // 2. 画面に「出勤」ボタンが表示されていることを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');
        
        // ステータスが勤務外であることを確認
        $status = $response->viewData('status');
        $this->assertEquals(Attendance::STATUS_OFF_DUTY, $status);
        
        // 出勤ボタンが表示されていることを確認（HTMLに「出勤」というテキストが含まれている）
        $response->assertSee('出勤', false);

        // 3. 出勤の処理を行う
        $clockInResponse = $this->actingAs($user)->post('/attendance/clock-in');
        
        $clockInResponse->assertRedirect(route('attendance'));

        // 処理後に画面上に表示されるステータスが「出勤中」になることを確認
        $afterResponse = $this->actingAs($user)->get('/attendance');
        $statusLabel = $afterResponse->viewData('statusLabel');
        $this->assertEquals('出勤中', $statusLabel, '画面上に表示されているステータスが「出勤中」になる');
        
        // データベースに勤怠レコードが作成されていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(Attendance::STATUS_WORKING, $attendance->status);
        $this->assertNotNull($attendance->clock_in_time);
    }

    /**
     * テストID: 6-2
     * 出勤は一日一回のみできる
     */
    public function test_clock_in_only_once_per_day()
    {
        // 1. ステータスが退勤済であるユーザーにログインする
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

        // 2. 勤務ボタンが表示されないことを確認する
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');
        
        // ステータスが退勤済であることを確認
        $status = $response->viewData('status');
        $this->assertEquals(Attendance::STATUS_FINISHED, $status, 'ステータスが退勤済である');
        
        // 退勤済みのメッセージが表示されていることを確認
        // 退勤済みの場合は、アクションボタンエリア全体が表示されず、「お疲れ様でした。」というメッセージのみが表示される
        // したがって、出勤ボタンも表示されない
        $response->assertSee('お疲れ様でした。', false);
        
        // 出勤ボタンのフォーム要素（action="/attendance/clock-in"）が存在しないことを確認
        // ヘッダーに「今月の出勤一覧」というリンクがあるため、単純に「出勤」という文字列を探すことはできない
        $htmlContent = $response->getContent();
        $this->assertStringNotContainsString('action="' . route('attendance.clock-in') . '"', $htmlContent, '画面上に「出勤」ボタンが表示されない');
    }

    /**
     * テストID: 6-3
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_displayed_in_list()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = $this->createTestUser();

        // 2. 出勤の処理を行う
        $clockInResponse = $this->actingAs($user)->post('/attendance/clock-in');
        $clockInResponse->assertRedirect(route('attendance'));

        // 出勤時刻を記録
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();
        $clockInTime = $attendance->clock_in_time;

        // 3. 勤怠一覧画面から出勤の日付を確認する
        $now = Carbon::now();
        $response = $this->actingAs($user)->get('/attendance/list/' . $now->year . '/' . $now->month);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');
        
        // 勤怠一覧画面に出勤時刻が正確に記録されていることを確認
        $attendances = $response->viewData('attendances');
        $todayAttendance = $attendances->firstWhere('date', Carbon::today());
        
        $this->assertNotNull($todayAttendance, '今日の勤怠レコードが存在する');
        $this->assertNotNull($todayAttendance->formatted_clock_in_time, '出勤時刻がフォーマットされている');
        $this->assertEquals(
            $clockInTime->format('H:i'),
            $todayAttendance->formatted_clock_in_time,
            '勤怠一覧画面に出勤時刻が正確に記録されている'
        );
    }
}

