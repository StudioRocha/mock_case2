<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId4DateTimeDisplayFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 曜日名の配列
     */
    private const WEEKDAY_NAMES = ['日', '月', '火', '水', '木', '金', '土'];

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
     * テストID: 4-1
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_datetime_display()
    {
        // 1. ユーザーを登録する（メール認証済み）
        $user = $this->createTestUser();

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('attendance.index');

        // 3. 画面に表示されている日時情報を確認する
        $now = Carbon::now();
        
        // 日付の確認
        $displayedDate = $response->viewData('date');
        $expectedDate = $now->format('Y年n月j日') . '(' . self::WEEKDAY_NAMES[$now->dayOfWeek] . ')';
        $this->assertEquals($expectedDate, $displayedDate, '画面上に表示されている日付が現在の日付と一致する');

        // 時刻の確認（分まで一致すればOK、秒は考慮しない）
        $displayedTime = $response->viewData('time');
        $expectedTime = $now->format('H:i');
        $this->assertEquals($expectedTime, $displayedTime, '画面上に表示されている時刻が現在の時刻と一致する');
    }
}

