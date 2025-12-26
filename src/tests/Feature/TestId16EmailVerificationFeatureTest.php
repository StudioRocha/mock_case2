<?php

namespace Tests\Feature;

use App\Mail\VerifyEmail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TestId16EmailVerificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト前のセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // メール送信をモック化（Mailhogを使用しているため、実際のメール送信を確認）
        // ただし、テストではMail::fake()を使用してメールが送信されたことを確認
        Mail::fake();
        
        // CSRFミドルウェアを無効化（テスト環境では不要）
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        // セッションをクリア（2回目以降のテストでセッションが残らないようにする）
        $this->session([]);
    }

    /**
     * テストID: 16-1
     * 会員登録後、認証メールが送信される
     */
    public function test_verification_email_sent_after_registration()
    {
        // 1. 会員登録をする
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        
        // ユーザーが作成されていることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user, 'ユーザーが作成されている');
        $this->assertFalse($user->hasVerifiedEmail(), 'メール認証が未完了である');

        // 2. 認証メールを送信する（会員登録時に自動送信される）
        // 登録したメールアドレス宛に認証メールが送信されていることを確認
        Mail::assertSent(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
        
        // メールの内容を確認
        Mail::assertSent(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    /**
     * テストID: 16-2
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_verification_button_navigates_to_verification_site()
    {
        // 1. メール認証導線画面を表示する
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $userRole->id,
        ]);
        // メール認証は未完了のまま

        // メール認証誘導画面を表示
        $response = $this->actingAs($user)->get('/email/verify');
        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-email');
        
        // 「認証はこちらから」ボタンが表示されていることを確認
        $response->assertSee('認証はこちらから', false);
        
        // 2. 「認証はこちらから」ボタンを押下するとメール認証サイト（MailHog）に遷移する
        // このボタンはMailHogの受信箱（http://localhost:8025/）へのリンク
        $response->assertSee('http://localhost:8025/', false);
    }

    /**
     * テストID: 16-3
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_verification_completion_navigates_to_attendance_page()
    {
        // 1. メール認証を完了する
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();
        
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $userRole->id,
        ]);
        // メール認証は未完了のまま

        // メール認証リンクを生成（実際のメール認証URLをシミュレート）
        $hash = sha1($user->email);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['id' => $user->id, 'hash' => $hash]
        );

        // 2. 勤怠登録画面を表示する
        // メール認証を完了（実際のメール認証リンクを使用）
        $response = $this->actingAs($user)->get($verificationUrl);
        
        // 勤怠登録画面に遷移することを確認
        $response->assertRedirect(route('attendance'));
        $response->assertSessionHas('success', 'メールアドレスの認証が完了しました。');
        
        // ユーザーが認証済みになっていることを確認
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail(), 'メール認証が完了している');
        
        // 勤怠登録画面にアクセスできることを確認
        $attendanceResponse = $this->actingAs($user)->get('/attendance');
        $attendanceResponse->assertStatus(200);
        $attendanceResponse->assertViewIs('attendance.index');
    }
}

