<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId1RegisterFeatureTest extends TestCase
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
     * テストID: 1-1
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_name_required_validation()
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertSessionHasErrorsIn('default', ['name']);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * テストID: 1-2
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_required_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrorsIn('default', ['email']);
        $this->assertDatabaseMissing('users', [
            'name' => 'テストユーザー',
        ]);
    }

    /**
     * テストID: 1-3
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_password_min_length_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrorsIn('default', ['password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * テストID: 1-4
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrorsIn('default', ['password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * テストID: 1-5
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_required_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrorsIn('default', ['password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * テストID: 1-6
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_user_registration_success()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        // データベースにユーザーが保存されていることを確認
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        // パスワードがハッシュ化されて保存されていることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('password123', $user->password);
        
        // 一般ユーザーのロールが設定されていることを確認
        $userRole = Role::where('name', Role::NAME_USER)->first();
        $this->assertEquals($userRole->id, $user->role_id);
    }
}

