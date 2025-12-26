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

class TestId12AdminAttendanceListFeatureTest extends TestCase
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
    private function createTestUser(string $name = 'テストユーザー', string $email = 'test@example.com'): User
    {
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();
        
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password123'),
            'role_id' => $userRole->id,
        ]);
        
        // メール認証済みにする
        $user->markEmailAsVerified();
        
        return $user;
    }

    /**
     * テストID: 12-1
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_all_users_attendance_displayed_correctly()
    {
        // 1. 管理者ユーザーにログインする
        $admin = $this->createTestAdminUser();
        
        // 複数の一般ユーザーを作成
        $user1 = $this->createTestUser('ユーザー1', 'user1@example.com');
        $user2 = $this->createTestUser('ユーザー2', 'user2@example.com');
        $user3 = $this->createTestUser('ユーザー3', 'user3@example.com');
        
        // 今日の日付で勤怠レコードを作成
        $today = Carbon::today();
        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(10, 0, 0),
            'clock_out_time' => $today->copy()->setTime(18, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        // user3は勤怠レコードを作成しない（勤怠がないユーザーも表示される）

        // 2. 勤怠一覧画面を開く
        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance.list');

        // その日の全ユーザーの勤怠情報が正確な値になっていることを確認
        $attendances = $response->viewData('attendances');
        $this->assertGreaterThanOrEqual(3, $attendances->count(), '全ユーザーの勤怠情報が表示されている');
        
        // 各ユーザーの勤怠情報が正確に表示されていることを確認
        $attendanceMap = $attendances->keyBy(function ($attendance) {
            return $attendance->user->id ?? null;
        });
        
        // ユーザー1の勤怠情報
        $user1Attendance = $attendanceMap->get($user1->id);
        $this->assertNotNull($user1Attendance);
        $this->assertEquals('09:00', $user1Attendance->formatted_clock_in_time);
        $this->assertEquals('17:00', $user1Attendance->formatted_clock_out_time);
        
        // ユーザー2の勤怠情報
        $user2Attendance = $attendanceMap->get($user2->id);
        $this->assertNotNull($user2Attendance);
        $this->assertEquals('10:00', $user2Attendance->formatted_clock_in_time);
        $this->assertEquals('18:00', $user2Attendance->formatted_clock_out_time);
        
        // ユーザー3の勤怠情報（勤怠がない場合も表示される）
        $user3Attendance = $attendanceMap->get($user3->id);
        $this->assertNotNull($user3Attendance);
        $this->assertNull($user3Attendance->formatted_clock_in_time);
        $this->assertNull($user3Attendance->formatted_clock_out_time);
    }

    /**
     * テストID: 12-2
     * 遷移した際に現在の日付が表示される
     */
    public function test_current_date_displayed_on_list_page()
    {
        // 1. 管理者ユーザーにログインする
        $admin = $this->createTestAdminUser();

        // 2. 勤怠一覧画面を開く
        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance.list');
        
        // 現在の日付が表示されていることを確認
        $now = Carbon::now();
        $currentYear = $response->viewData('currentYear');
        $currentMonth = $response->viewData('currentMonth');
        $currentDay = $response->viewData('currentDay');
        
        $this->assertEquals($now->year, $currentYear, '現在の年が表示されている');
        $this->assertEquals($now->month, $currentMonth, '現在の月が表示されている');
        $this->assertEquals($now->day, $currentDay, '現在の日が表示されている');
        
        // ビューに現在の日付が表示されていることを確認（YYYY/MM/DD形式）
        $expectedDateString = $now->year . '/' . str_pad($now->month, 2, '0', STR_PAD_LEFT) . '/' . str_pad($now->day, 2, '0', STR_PAD_LEFT);
        $response->assertSee($expectedDateString, false);
    }

    /**
     * テストID: 12-3
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_previous_day_displayed_when_prev_button_clicked()
    {
        // 1. 管理者ユーザーにログインする
        $admin = $this->createTestAdminUser();
        
        // 前日に勤怠レコードを作成
        $yesterday = Carbon::yesterday();
        $user = $this->createTestUser();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $yesterday,
            'clock_in_time' => $yesterday->copy()->setTime(9, 0, 0),
            'clock_out_time' => $yesterday->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠一覧画面を開く
        $now = Carbon::now();
        $response = $this->actingAs($admin)->get('/admin/attendance/list/' . $now->year . '/' . $now->month . '/' . $now->day);
        $response->assertStatus(200);

        // 3. 「前日」ボタンを押す
        $prevYear = $response->viewData('prevYear');
        $prevMonth = $response->viewData('prevMonth');
        $prevDay = $response->viewData('prevDay');
        $prevDayResponse = $this->actingAs($admin)->get('/admin/attendance/list/' . $prevYear . '/' . $prevMonth . '/' . $prevDay);
        
        $prevDayResponse->assertStatus(200);
        $prevDayResponse->assertViewIs('admin.attendance.list');
        
        // 前日の日付が表示されていることを確認
        $displayedYear = $prevDayResponse->viewData('currentYear');
        $displayedMonth = $prevDayResponse->viewData('currentMonth');
        $displayedDay = $prevDayResponse->viewData('currentDay');
        
        $this->assertEquals($yesterday->year, $displayedYear, '前日の年が表示されている');
        $this->assertEquals($yesterday->month, $displayedMonth, '前日の月が表示されている');
        $this->assertEquals($yesterday->day, $displayedDay, '前日の日が表示されている');
        
        // 前日の勤怠レコードが表示されていることを確認
        $attendances = $prevDayResponse->viewData('attendances');
        $attendanceIds = $attendances->pluck('id')->filter()->toArray();
        $this->assertContains($attendance->id, $attendanceIds, '前日の勤怠レコードが表示されている');
    }

    /**
     * テストID: 12-4
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_next_day_displayed_when_next_button_clicked()
    {
        // 1. 管理者ユーザーにログインする
        $admin = $this->createTestAdminUser();
        
        // 翌日に勤怠レコードを作成
        $tomorrow = Carbon::tomorrow();
        $user = $this->createTestUser();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $tomorrow,
            'clock_in_time' => $tomorrow->copy()->setTime(9, 0, 0),
            'clock_out_time' => $tomorrow->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠一覧画面を開く
        $now = Carbon::now();
        $response = $this->actingAs($admin)->get('/admin/attendance/list/' . $now->year . '/' . $now->month . '/' . $now->day);
        $response->assertStatus(200);

        // 3. 「翌日」ボタンを押す
        $nextYear = $response->viewData('nextYear');
        $nextMonth = $response->viewData('nextMonth');
        $nextDay = $response->viewData('nextDay');
        $nextDayResponse = $this->actingAs($admin)->get('/admin/attendance/list/' . $nextYear . '/' . $nextMonth . '/' . $nextDay);
        
        $nextDayResponse->assertStatus(200);
        $nextDayResponse->assertViewIs('admin.attendance.list');
        
        // 翌日の日付が表示されていることを確認
        $displayedYear = $nextDayResponse->viewData('currentYear');
        $displayedMonth = $nextDayResponse->viewData('currentMonth');
        $displayedDay = $nextDayResponse->viewData('currentDay');
        
        $this->assertEquals($tomorrow->year, $displayedYear, '翌日の年が表示されている');
        $this->assertEquals($tomorrow->month, $displayedMonth, '翌日の月が表示されている');
        $this->assertEquals($tomorrow->day, $displayedDay, '翌日の日が表示されている');
        
        // 翌日の勤怠レコードが表示されていることを確認
        $attendances = $nextDayResponse->viewData('attendances');
        $attendanceIds = $attendances->pluck('id')->filter()->toArray();
        $this->assertContains($attendance->id, $attendanceIds, '翌日の勤怠レコードが表示されている');
    }
}

