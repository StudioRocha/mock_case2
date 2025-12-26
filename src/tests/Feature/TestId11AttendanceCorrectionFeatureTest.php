<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Role;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId11AttendanceCorrectionFeatureTest extends TestCase
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
     * テストID: 11-1
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_time_after_clock_out_time_shows_error()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 出勤時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
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
        $this->assertTrue($errors->has('clock_time'), '出勤時間が不適切な値であることを示すエラーが表示される');
    }

    /**
     * テストID: 11-2
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_time_after_clock_out_time_shows_error()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 休憩開始時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
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
     * テストID: 11-3
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_time_after_clock_out_time_shows_error()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 休憩終了時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
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
     * テストID: 11-4
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_empty_note_shows_error()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細ページを開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        // 3. 備考欄を未入力のまま保存処理をする
        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
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

    /**
     * テストID: 11-5
     * 修正申請処理が実行される
     */
    public function test_correction_request_submitted()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細を修正し保存処理をする
        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
            'clock_in_time' => '09:30',
            'clock_out_time' => '18:00',
            'break_start_times' => ['12:00'],
            'break_end_times' => ['13:00'],
            'note' => '修正申請のテスト',
        ]);

        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHas('success', '修正申請が完了しました。');

        // 修正申請レコードが作成されていることを確認
        $correctionRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->first();
        $this->assertNotNull($correctionRequest, '修正申請が作成されている');

        // 3. 管理者ユーザーで承認画面と申請一覧画面を確認する
        $admin = $this->createTestAdminUser();
        
        // 管理者の申請一覧画面を確認
        $adminListResponse = $this->actingAs($admin)->get('/stamp_correction_request/list?status=pending');
        $adminListResponse->assertStatus(200);
        $adminListResponse->assertSee('修正申請のテスト', false);
        
        // 管理者の承認画面を確認
        $adminDetailResponse = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $correctionRequest->id);
        $adminDetailResponse->assertStatus(200);
        $adminDetailResponse->assertSee('修正申請のテスト', false);
    }

    /**
     * テストID: 11-6
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_all_pending_requests_displayed()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance1 = $this->createFinishedAttendance($user);
        
        // 異なる日付で2つ目の勤怠レコードを作成
        $yesterday = Carbon::yesterday();
        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'date' => $yesterday,
            'clock_in_time' => $yesterday->copy()->setTime(9, 0, 0),
            'clock_out_time' => $yesterday->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 2. 勤怠詳細を修正し保存処理をする
        // 1つ目の修正申請
        $this->actingAs($user)->post('/attendance/detail/' . $attendance1->id . '/correction-request', [
            'clock_in_time' => '09:30',
            'clock_out_time' => '18:00',
            'break_start_times' => [],
            'break_end_times' => [],
            'note' => '修正申請1',
        ]);

        // 2つ目の修正申請
        $this->actingAs($user)->post('/attendance/detail/' . $attendance2->id . '/correction-request', [
            'clock_in_time' => '10:00',
            'clock_out_time' => '19:00',
            'break_start_times' => [],
            'break_end_times' => [],
            'note' => '修正申請2',
        ]);

        // 3. 申請一覧画面を確認する
        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=pending');
        
        $response->assertStatus(200);
        $response->assertViewIs('stamp_correction_request.list');
        
        // 申請一覧に自分の申請が全て表示されていることを確認
        $requests = $response->viewData('requests');
        $this->assertGreaterThanOrEqual(2, $requests->count(), '申請一覧に自分の申請が全て表示されている');
        
        $requestNotes = $requests->pluck('requested_note')->toArray();
        $this->assertContains('修正申請1', $requestNotes);
        $this->assertContains('修正申請2', $requestNotes);
    }

    /**
     * テストID: 11-7
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_all_approved_requests_displayed()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細を修正し保存処理をする
        $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
            'clock_in_time' => '09:30',
            'clock_out_time' => '18:00',
            'break_start_times' => [],
            'break_end_times' => [],
            'note' => '承認待ちの申請',
        ]);

        $correctionRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->first();

        // 3. 申請一覧画面を開く
        // 4. 管理者が承認した修正申請が全て表示されていることを確認
        $admin = $this->createTestAdminUser();
        
        // 管理者が承認
        $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $correctionRequest->id);
        
        // 一般ユーザーで承認済みタブを確認
        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=approved');
        
        $response->assertStatus(200);
        $response->assertViewIs('stamp_correction_request.list');
        
        // 承認済みに管理者が承認した申請が全て表示されていることを確認
        $requests = $response->viewData('requests');
        $this->assertGreaterThanOrEqual(1, $requests->count(), '承認済みに管理者が承認した申請が全て表示されている');
        
        $approvedRequest = $requests->first();
        $this->assertEquals(StampCorrectionRequest::STATUS_APPROVED, $approvedRequest->status);
        $this->assertEquals('承認待ちの申請', $approvedRequest->requested_note);
    }

    /**
     * テストID: 11-8
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_attendance_detail()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        $user = $this->createTestUser();
        $attendance = $this->createFinishedAttendance($user);

        // 2. 勤怠詳細を修正し保存処理をする
        $this->actingAs($user)->post('/attendance/detail/' . $attendance->id . '/correction-request', [
            'clock_in_time' => '09:30',
            'clock_out_time' => '18:00',
            'break_start_times' => [],
            'break_end_times' => [],
            'note' => '詳細ボタンのテスト',
        ]);

        $correctionRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->first();

        // 3. 申請一覧画面を開く
        $listResponse = $this->actingAs($user)->get('/stamp_correction_request/list?status=pending');
        $listResponse->assertStatus(200);
        
        // 「詳細」ボタンが表示されていることを確認
        $listResponse->assertSee('詳細', false);

        // 4. 「詳細」ボタンを押す
        $detailResponse = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('attendance.detail');
        
        // 勤怠詳細画面に遷移することを確認
        $detailAttendance = $detailResponse->viewData('attendance');
        $this->assertEquals($attendance->id, $detailAttendance->id, '勤怠詳細画面に遷移する');
    }
}

