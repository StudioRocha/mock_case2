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

class TestId9AttendanceListFeatureTest extends TestCase
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
     * テストID: 9-1
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_all_attendance_records_displayed()
    {
        // 1. 勤怠情報が登録されたユーザーにログインする
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

        // 2. 勤怠一覧ページを開く
        $now = Carbon::now();
        $response = $this->actingAs($user)->get('/attendance/list/' . $now->year . '/' . $now->month);
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');

        // 3. 自分の勤怠情報がすべて表示されていることを確認する
        $attendances = $response->viewData('attendances');
        $this->assertEquals(3, $attendances->count(), 'すべての勤怠レコードが表示されている');
        
        // 各勤怠レコードが表示されていることを確認
        $attendanceIds = $attendances->pluck('id')->toArray();
        $this->assertContains($attendance1->id, $attendanceIds, '1つ目の勤怠レコードが表示されている');
        $this->assertContains($attendance2->id, $attendanceIds, '2つ目の勤怠レコードが表示されている');
        $this->assertContains($attendance3->id, $attendanceIds, '3つ目の勤怠レコードが表示されている');
    }

    /**
     * テストID: 9-2
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_displayed_on_list_page()
    {
        // 1. ユーザーにログインをする
        $user = $this->createTestUser();

        // 2. 勤怠一覧ページを開く
        $response = $this->actingAs($user)->get('/attendance/list');
        
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');
        
        // 現在の年月が表示されていることを確認
        $now = Carbon::now();
        $currentYear = $response->viewData('currentYear');
        $currentMonth = $response->viewData('currentMonth');
        
        $this->assertEquals($now->year, $currentYear, '現在の年が表示されている');
        $this->assertEquals($now->month, $currentMonth, '現在の月が表示されている');
        
        // ビューに現在の年月が表示されていることを確認（YYYY/MM形式）
        $expectedDateString = $now->year . '/' . str_pad($now->month, 2, '0', STR_PAD_LEFT);
        $response->assertSee($expectedDateString, false);
    }

    /**
     * テストID: 9-3
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_displayed_when_prev_button_clicked()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
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
        $response = $this->actingAs($user)->get('/attendance/list/' . $now->year . '/' . $now->month);
        $response->assertStatus(200);

        // 3. 「前月」ボタンを押す
        $prevYear = $response->viewData('prevYear');
        $prevMonth = $response->viewData('prevMonth');
        $prevMonthResponse = $this->actingAs($user)->get('/attendance/list/' . $prevYear . '/' . $prevMonth);
        
        $prevMonthResponse->assertStatus(200);
        $prevMonthResponse->assertViewIs('attendance.list');
        
        // 前月の年月が表示されていることを確認
        $displayedYear = $prevMonthResponse->viewData('currentYear');
        $displayedMonth = $prevMonthResponse->viewData('currentMonth');
        
        $this->assertEquals($lastMonth->year, $displayedYear, '前月の年が表示されている');
        $this->assertEquals($lastMonth->month, $displayedMonth, '前月の月が表示されている');
        
        // 前月の勤怠レコードが表示されていることを確認
        $attendances = $prevMonthResponse->viewData('attendances');
        $attendanceIds = $attendances->pluck('id')->toArray();
        $this->assertContains($attendance->id, $attendanceIds, '前月の勤怠レコードが表示されている');
    }

    /**
     * テストID: 9-4
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_displayed_when_next_button_clicked()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
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
        $response = $this->actingAs($user)->get('/attendance/list/' . $now->year . '/' . $now->month);
        $response->assertStatus(200);

        // 3. 「翌月」ボタンを押す
        $nextYear = $response->viewData('nextYear');
        $nextMonthValue = $response->viewData('nextMonth');
        $nextMonthResponse = $this->actingAs($user)->get('/attendance/list/' . $nextYear . '/' . $nextMonthValue);
        
        $nextMonthResponse->assertStatus(200);
        $nextMonthResponse->assertViewIs('attendance.list');
        
        // 翌月の年月が表示されていることを確認
        $displayedYear = $nextMonthResponse->viewData('currentYear');
        $displayedMonth = $nextMonthResponse->viewData('currentMonth');
        
        $this->assertEquals($nextMonth->year, $displayedYear, '翌月の年が表示されている');
        $this->assertEquals($nextMonth->month, $displayedMonth, '翌月の月が表示されている');
        
        // 翌月の勤怠レコードが表示されていることを確認
        $attendances = $nextMonthResponse->viewData('attendances');
        $attendanceIds = $attendances->pluck('id')->toArray();
        $this->assertContains($attendance->id, $attendanceIds, '翌月の勤怠レコードが表示されている');
    }

    /**
     * テストID: 9-5
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_detail_page()
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

        // 2. 勤怠一覧ページを開く
        $now = Carbon::now();
        $response = $this->actingAs($user)->get('/attendance/list/' . $now->year . '/' . $now->month);
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');
        
        // 「詳細」ボタンが表示されていることを確認
        $response->assertSee('詳細', false);

        // 3. 「詳細」ボタンを押下する
        $detailResponse = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('attendance.detail');
        
        // 詳細画面に該当の勤怠情報が表示されていることを確認
        $detailAttendance = $detailResponse->viewData('attendance');
        $this->assertEquals($attendance->id, $detailAttendance->id, '該当の勤怠レコードが表示されている');
    }
}

