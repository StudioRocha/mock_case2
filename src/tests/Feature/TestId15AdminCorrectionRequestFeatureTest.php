<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakCorrection;
use App\Models\BreakTime;
use App\Models\Role;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId15AdminCorrectionRequestFeatureTest extends TestCase
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
     * 修正申請を作成
     */
    private function createCorrectionRequest(User $user, Attendance $attendance, string $status = StampCorrectionRequest::STATUS_PENDING): StampCorrectionRequest
    {
        $correctionRequest = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_time' => $attendance->date->copy()->setTime(9, 30, 0),
            'requested_clock_out_time' => $attendance->date->copy()->setTime(18, 0, 0),
            'requested_note' => '修正申請のテスト',
            'status' => $status,
        ]);
        
        // 休憩修正レコードを作成
        BreakCorrection::create([
            'stamp_correction_request_id' => $correctionRequest->id,
            'requested_break_start_time' => $attendance->date->copy()->setTime(12, 0, 0),
            'requested_break_end_time' => $attendance->date->copy()->setTime(13, 0, 0),
        ]);
        
        return $correctionRequest;
    }

    /**
     * テストID: 15-1
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_all_pending_requests_displayed()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        
        // 複数の一般ユーザーと修正申請を作成
        $user1 = $this->createTestUser('ユーザー1', 'user1@example.com');
        $user2 = $this->createTestUser('ユーザー2', 'user2@example.com');
        
        $today = Carbon::today();
        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $yesterday = Carbon::yesterday();
        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'date' => $yesterday,
            'clock_in_time' => $yesterday->copy()->setTime(9, 0, 0),
            'clock_out_time' => $yesterday->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $request1 = $this->createCorrectionRequest($user1, $attendance1, StampCorrectionRequest::STATUS_PENDING);
        $request2 = $this->createCorrectionRequest($user2, $attendance2, StampCorrectionRequest::STATUS_PENDING);

        // 2. 修正申請一覧ページを開き、承認待ちのタブを開く
        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?status=pending');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.stamp_correction_request.list');

        // 全ユーザーの未承認の修正申請が表示されることを確認
        $requests = $response->viewData('requests');
        $this->assertGreaterThanOrEqual(2, $requests->count(), '全ユーザーの未承認の修正申請が表示される');
        
        $requestIds = $requests->pluck('id')->toArray();
        $this->assertContains($request1->id, $requestIds, 'ユーザー1の修正申請が表示されている');
        $this->assertContains($request2->id, $requestIds, 'ユーザー2の修正申請が表示されている');
    }

    /**
     * テストID: 15-2
     * 承認済みの修正申請が全て表示されている
     */
    public function test_all_approved_requests_displayed()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        
        // 複数の一般ユーザーと修正申請を作成
        $user1 = $this->createTestUser('ユーザー1', 'user1@example.com');
        $user2 = $this->createTestUser('ユーザー2', 'user2@example.com');
        
        $today = Carbon::today();
        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $yesterday = Carbon::yesterday();
        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'date' => $yesterday,
            'clock_in_time' => $yesterday->copy()->setTime(9, 0, 0),
            'clock_out_time' => $yesterday->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $request1 = $this->createCorrectionRequest($user1, $attendance1, StampCorrectionRequest::STATUS_APPROVED);
        $request2 = $this->createCorrectionRequest($user2, $attendance2, StampCorrectionRequest::STATUS_APPROVED);

        // 2. 修正申請一覧ページを開き、承認済みのタブを開く
        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?status=approved');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.stamp_correction_request.list');

        // 全ユーザーの承認済みの修正申請が表示されることを確認
        $requests = $response->viewData('requests');
        $this->assertGreaterThanOrEqual(2, $requests->count(), '全ユーザーの承認済みの修正申請が表示される');
        
        $requestIds = $requests->pluck('id')->toArray();
        $this->assertContains($request1->id, $requestIds, 'ユーザー1の承認済み修正申請が表示されている');
        $this->assertContains($request2->id, $requestIds, 'ユーザー2の承認済み修正申請が表示されている');
    }

    /**
     * テストID: 15-3
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_correction_request_detail_displayed_correctly()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        
        // 修正申請を作成
        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(17, 0, 0),
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        $requestedClockInTime = $today->copy()->setTime(9, 30, 0);
        $requestedClockOutTime = $today->copy()->setTime(18, 0, 0);
        $requestedNote = '修正申請の詳細テスト';
        
        $correctionRequest = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_time' => $requestedClockInTime,
            'requested_clock_out_time' => $requestedClockOutTime,
            'requested_note' => $requestedNote,
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);
        
        $requestedBreakStartTime = $today->copy()->setTime(12, 0, 0);
        $requestedBreakEndTime = $today->copy()->setTime(13, 0, 0);
        BreakCorrection::create([
            'stamp_correction_request_id' => $correctionRequest->id,
            'requested_break_start_time' => $requestedBreakStartTime,
            'requested_break_end_time' => $requestedBreakEndTime,
        ]);

        // 2. 修正申請の詳細画面を開く
        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $correctionRequest->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.stamp_correction_request.detail');

        // 申請内容が正しく表示されていることを確認
        $stampCorrectionRequest = $response->viewData('stampCorrectionRequest');
        $this->assertEquals($correctionRequest->id, $stampCorrectionRequest->id, '修正申請が正しく表示されている');
        
        $displayClockInTime = $response->viewData('displayClockInTime');
        $displayClockOutTime = $response->viewData('displayClockOutTime');
        $displayNote = $response->viewData('displayNote');
        
        $this->assertEquals($requestedClockInTime->format('H:i'), $displayClockInTime, '申請された出勤時刻が正しく表示されている');
        $this->assertEquals($requestedClockOutTime->format('H:i'), $displayClockOutTime, '申請された退勤時刻が正しく表示されている');
        $this->assertEquals($requestedNote, $displayNote, '申請された備考が正しく表示されている');
        
        // 休憩時間も確認
        $breakDetails = $response->viewData('breakDetails');
        $validBreaks = array_filter($breakDetails, function ($break) {
            return !empty($break['start_time']) && !empty($break['end_time']);
        });
        $this->assertGreaterThanOrEqual(1, count($validBreaks), '申請された休憩時間が正しく表示されている');
        
        $firstBreak = array_values($validBreaks)[0];
        $this->assertEquals($requestedBreakStartTime->format('H:i'), $firstBreak['start_time'], '申請された休憩開始時刻が正しく表示されている');
        $this->assertEquals($requestedBreakEndTime->format('H:i'), $firstBreak['end_time'], '申請された休憩終了時刻が正しく表示されている');
    }

    /**
     * テストID: 15-4
     * 修正申請の承認処理が正しく行われる
     */
    public function test_approval_process_works_correctly()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = $this->createTestAdminUser();
        $user = $this->createTestUser();
        
        // 修正申請を作成
        $today = Carbon::today();
        $originalClockInTime = $today->copy()->setTime(9, 0, 0);
        $originalClockOutTime = $today->copy()->setTime(17, 0, 0);
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in_time' => $originalClockInTime,
            'clock_out_time' => $originalClockOutTime,
            'status' => Attendance::STATUS_FINISHED,
        ]);
        
        // 既存の休憩レコードを作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $today->copy()->setTime(12, 0, 0),
            'break_end_time' => $today->copy()->setTime(12, 30, 0),
        ]);
        
        $requestedClockInTime = $today->copy()->setTime(9, 30, 0);
        $requestedClockOutTime = $today->copy()->setTime(18, 0, 0);
        $requestedNote = '承認テスト';
        
        $correctionRequest = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_time' => $requestedClockInTime,
            'requested_clock_out_time' => $requestedClockOutTime,
            'requested_note' => $requestedNote,
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);
        
        $requestedBreakStartTime = $today->copy()->setTime(12, 0, 0);
        $requestedBreakEndTime = $today->copy()->setTime(13, 0, 0);
        BreakCorrection::create([
            'stamp_correction_request_id' => $correctionRequest->id,
            'requested_break_start_time' => $requestedBreakStartTime,
            'requested_break_end_time' => $requestedBreakEndTime,
        ]);

        // 2. 修正申請の詳細画面で「承認」ボタンを押す
        $response = $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $correctionRequest->id);
        
        $response->assertRedirect(route('admin.stamp_correction_request.approve', ['attendance_correct_request_id' => $correctionRequest->id]));
        $response->assertSessionHas('success', '修正申請を承認しました。');

        // 修正申請が承認され、勤怠情報が更新されることを確認
        $correctionRequest->refresh();
        $this->assertEquals(StampCorrectionRequest::STATUS_APPROVED, $correctionRequest->status, '修正申請が承認されている');
        $this->assertNotNull($correctionRequest->approved_at, '承認日時が記録されている');
        
        // 勤怠情報が更新されていることを確認
        $attendance->refresh();
        $this->assertEquals($requestedClockInTime->format('Y-m-d H:i:s'), $attendance->clock_in_time->format('Y-m-d H:i:s'), '出勤時刻が更新されている');
        $this->assertEquals($requestedClockOutTime->format('Y-m-d H:i:s'), $attendance->clock_out_time->format('Y-m-d H:i:s'), '退勤時刻が更新されている');
        $this->assertEquals($requestedNote, $attendance->note, '備考が更新されている');
        
        // 既存の休憩レコードが削除され、新しい休憩レコードが作成されていることを確認
        $oldBreaks = BreakTime::where('attendance_id', $attendance->id)
            ->where('break_start_time', $today->copy()->setTime(12, 0, 0))
            ->where('break_end_time', $today->copy()->setTime(12, 30, 0))
            ->count();
        $this->assertEquals(0, $oldBreaks, '既存の休憩レコードが削除されている');
        
        $newBreak = BreakTime::where('attendance_id', $attendance->id)
            ->where('break_start_time', $requestedBreakStartTime)
            ->where('break_end_time', $requestedBreakEndTime)
            ->first();
        $this->assertNotNull($newBreak, '新しい休憩レコードが作成されている');
    }
}

