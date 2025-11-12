<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Actionsクラスの設定（会員登録のみ有効）
        Fortify::createUsersUsing(CreateNewUser::class);

        // ログイン画面のビューを設定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 会員登録画面のビューを設定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // 一般ユーザーのみログイン可能にする
        Fortify::authenticateUsing(function (Request $request) {
            // バリデーション
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ], [
                'email.required' => 'メールアドレスを入力してください',
                'email.email' => 'メールアドレスの形式が正しくありません',
                'password.required' => 'パスワードを入力してください',
            ]);

            $user = User::where('email', $request->email)
                ->where('role', 'user')
                ->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // ログイン失敗時のエラーメッセージ
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません'],
            ]);
        });

        // ログイン成功後のリダイレクト先
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    return redirect()->route('attendance');
                }
            };
        });

        // 会員登録成功後のリダイレクト先
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse {
                public function toResponse($request)
                {
                    return redirect()->route('attendance');
                }
            };
        });

        // ログアウト後のリダイレクト先
        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse {
                public function toResponse($request)
                {
                    return redirect()->route('login');
                }
            };
        });

        // レート制限の設定
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(5)->by($email.$request->ip());
        });
    }
}

