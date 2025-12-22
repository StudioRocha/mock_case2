<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestId2LoginFeatureTest extends TestCase
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
     * テスト用の一般ユーザーを作成
     */
    private function createTestUser(): User
    {
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();
        
        return User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $userRole->id,
        ]);
    }

    /**
     * テストID: 2-1
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_required_validation()
    {
        // 1. ユーザーを登録する
        $this->createTestUser();

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrorsIn('default', ['email']);
    }

    /**
     * テストID: 2-2
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_required_validation()
    {
        // 1. ユーザーを登録する
        $this->createTestUser();

        // 2. パスワード以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrorsIn('default', ['password']);
    }

    /**
     * テストID: 2-3
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_login_failed_with_wrong_credentials()
    {
        // 1. ユーザーを登録する
        $this->createTestUser();

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrorsIn('default', ['email']);
        
        // エラーメッセージの内容を確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('email'));
        $this->assertContains('ログイン情報が登録されていません', $errors->get('email'));
    }
}

