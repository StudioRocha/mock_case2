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

class TestId14AdminUserInfoFeatureTest extends TestCase
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
     * テストID: 14-1
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_all_users_name_and_email_displayed()
    {
        // 1. 管理者でログインする
        $admin = $this->createTestAdminUser();
        
        // 複数の一般ユーザーを作成
        $user1 = $this->createTestUser('ユーザー1', 'user1@example.com');
        $user2 = $this->createTestUser('ユーザー2', 'user2@example.com');
        $user3 = $this->createTestUser('ユーザー3', 'user3@example.com');

        // 2. スタッフ一覧ページを開く
        $response = $this->actingAs($admin)->get('/admin/staff/list');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.staff.list');

        // 全ての一般ユーザーの氏名とメールアドレスが正しく表示されていることを確認
        $staffs = $response->viewData('staffs');
        $this->assertGreaterThanOrEqual(3, $staffs->count(), '全一般ユーザーが表示されている');
        
        // 各ユーザーの氏名とメールアドレスが表示されていることを確認
        $staffMap = $staffs->keyBy('id');
        
        $staff1 = $staffMap->get($user1->id);
        $this->assertNotNull($staff1);
        $this->assertEquals($user1->name, $staff1->name, 'ユーザー1の氏名が正しく表示されている');
        $this->assertEquals($user1->email, $staff1->email, 'ユーザー1のメールアドレスが正しく表示されている');
        
        $staff2 = $staffMap->get($user2->id);
        $this->assertNotNull($staff2);
        $this->assertEquals($user2->name, $staff2->name, 'ユーザー2の氏名が正しく表示されている');
        $this->assertEquals($user2->email, $staff2->email, 'ユーザー2のメールアドレスが正しく表示されている');
        
        $staff3 = $staffMap->get($user3->id);
        $this->assertNotNull($staff3);
        $this->assertEquals($user3->name, $staff3->name, 'ユーザー3の氏名が正しく表示されている');
        $this->assertEquals($user3->email, $staff3->email, 'ユーザー3のメールアドレスが正しく表示されている');
        
        // ビューに氏名とメールアドレスが表示されていることを確認
        $response->assertSee($user1->name, false);
        $response->assertSee($user1->email, false);
        $response->assertSee($user2->name, false);
        $response->assertSee($user2->email, false);
        $response->assertSee($user3->name, false);
        $response->assertSee($user3->email, false);
    }

    /**
     * テストID: 14-2
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_user_attendance_displayed_correctly()
    {
        // 1. 管理者ユーザーでログインする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        
        // 現在の月に複数の勤怠レコードを作成
        $today = Carbon::today();
        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->copy()->subDays(2),
            'clock_in_time' => $today->copy()->subDays(2)->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->subDays(2)->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->copy()->subDays(1),
            'clock_in_time' => $today->copy()->subDays(1)->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->subDays(1)->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $attendance3 = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 選択したユーザーの勤怠一覧ページを開く
        $now = Carbon::now();
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '/' . $now->year . '/' . $now->month);
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance.monthly');

        // 勤怠情報が正確に表示されることを確認
        $attendances = $response->viewData('attendances');
        $this->assertGreaterThanOrEqual(3, $attendances->count(), '勤怠情報が表示されている');
        
        // 各勤怠レコードが表示されていることを確認
        $attendanceIds = $attendances->pluck('id')->filter()->toArray();
        $this->assertContains($attendance1->id, $attendanceIds, '1つ目の勤怠レコードが表示されている');
        $this->assertContains($attendance2->id, $attendanceIds, '2つ目の勤怠レコードが表示されている');
        $this->assertContains($attendance3->id, $attendanceIds, '3つ目の勤怠レコードが表示されている');
    }

    /**
     * テストID: 14-3
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_displayed_when_prev_button_clicked()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        
        // 前月に勤怠レコードを作成
        $lastMonth = Carbon::now()->subMonth();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $lastMonth->copy()->setDay(15),
            'clock_in_time' => $lastMonth->copy()->setDay(15)->setTime(9, 0, 0),
            'clock_out_time' => $lastMonth->copy()->setDay(15)->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠一覧ページを開く
        $now = Carbon::now();
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '/' . $now->year . '/' . $now->month);
        $response->assertStatus(200);

        // 3. 「前月」ボタンを押す
        $prevYear = $response->viewData('prevYear');
        $prevMonth = $response->viewData('prevMonth');
        $prevMonthResponse = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '/' . $prevYear . '/' . $prevMonth);
        
        $prevMonthResponse->assertStatus(200);
        $prevMonthResponse->assertViewIs('admin.attendance.monthly');
        
        // 前月の年月が表示されていることを確認
        $displayedYear = $prevMonthResponse->viewData('currentYear');
        $displayedMonth = $prevMonthResponse->viewData('currentMonth');
        
        $this->assertEquals($lastMonth->year, $displayedYear, '前月の年が表示されている');
        $this->assertEquals($lastMonth->month, $displayedMonth, '前月の月が表示されている');
        
        // 前月の勤怠レコードが表示されていることを確認
        $attendances = $prevMonthResponse->viewData('attendances');
        $attendanceIds = $attendances->pluck('id')->filter()->toArray();
        $this->assertContains($attendance->id, $attendanceIds, '前月の勤怠レコードが表示されている');
    }

    /**
     * テストID: 14-4
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_displayed_when_next_button_clicked()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        
        // 翌月に勤怠レコードを作成
        $nextMonth = Carbon::now()->addMonth();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->setDay(15),
            'clock_in_time' => $nextMonth->copy()->setDay(15)->setTime(9, 0, 0),
            'clock_out_time' => $nextMonth->copy()->setDay(15)->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠一覧ページを開く
        $now = Carbon::now();
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '/' . $now->year . '/' . $now->month);
        $response->assertStatus(200);

        // 3. 「翌月」ボタンを押す
        $nextYear = $response->viewData('nextYear');
        $nextMonthValue = $response->viewData('nextMonth');
        $nextMonthResponse = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '/' . $nextYear . '/' . $nextMonthValue);
        
        $nextMonthResponse->assertStatus(200);
        $nextMonthResponse->assertViewIs('admin.attendance.monthly');
        
        // 翌月の年月が表示されていることを確認
        $displayedYear = $nextMonthResponse->viewData('currentYear');
        $displayedMonth = $nextMonthResponse->viewData('currentMonth');
        
        $this->assertEquals($nextMonth->year, $displayedYear, '翌月の年が表示されている');
        $this->assertEquals($nextMonth->month, $displayedMonth, '翌月の月が表示されている');
        
        // 翌月の勤怠レコードが表示されていることを確認
        $attendances = $nextMonthResponse->viewData('attendances');
        $attendanceIds = $attendances->pluck('id')->filter()->toArray();
        $this->assertContains($attendance->id, $attendanceIds, '翌月の勤怠レコードが表示されている');
    }

    /**
     * テストID: 14-5
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_detail_page()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
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

        // 2. 勤怠一覧ページを開く
        $now = Carbon::now();
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '/' . $now->year . '/' . $now->month);
        $response->assertStatus(200);
        $response->assertViewIs('admin.attendance.monthly');
        
        // 「詳細」ボタンが表示されていることを確認
        $response->assertSee('詳細', false);

        // 3. 「詳細」ボタンを押下する
        $detailResponse = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);
        
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('admin.attendance.detail');
        
        // その日の勤怠詳細画面に遷移することを確認
        $detailAttendance = $detailResponse->viewData('attendance');
        $this->assertEquals($attendance->id, $detailAttendance->id, 'その日の勤怠詳細画面に遷移する');
    }
}



