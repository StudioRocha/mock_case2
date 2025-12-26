<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminLoginRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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

        // 一般ユーザー用ログイン画面のビューを設定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 会員登録画面のビューを設定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // 一般ユーザー用ログインリクエストのバリデーションと認証をカスタマイズ（FN006: ログイン認証機能（一般ユーザー））
        Fortify::authenticateUsing(function (Request $request) {
            // バリデーション（FN009: エラーメッセージ表示）
            // FormRequestを使用してバリデーション（FormRequest + トレイトの併用）
            $formRequest = LoginRequest::createFrom($request);
            $formRequest->setContainer(app());
            $formRequest->setRedirector(app('redirect'));
            
            // FormRequestのバリデーションを実行（セッションにエラーが保存される）
            $formRequest->validateResolved();

            // 一般ユーザーログイン処理
            $userRole = Role::where('name', Role::NAME_USER)->first();

            $user = User::with('role')
                ->where('email', $request->email)
                ->where('role_id', $userRole ? $userRole->id : null)
                ->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // ログイン失敗時のエラーメッセージ（FN009: 入力情報が誤っている場合）
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません'],
            ]);
        });

        // ログイン成功後のリダイレクト先（一般ユーザー・管理者で分岐）
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    /** @var User|null $user */
                    $user = $request->user();
                    
                    if ($user && $user->isAdmin()) {
                        // 管理者は常に勤怠一覧画面（管理者）にリダイレクト
                        return redirect()->route('admin.attendance.list');
                    }
                    
                    // 一般ユーザーでメール認証が完了していない場合は認証誘導画面へ
                    if ($user && $user->isUser() && !$user->hasVerifiedEmail()) {
                        return redirect()->route('verification.notice')
                            ->with('info', 'メールアドレスの認証が完了していません。認証メールをご確認ください。');
                    }
                    
                    return redirect()->intended(route('attendance'));
                }
            };
        });

        // 会員登録成功後のリダイレクト先（メール認証誘導画面）
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse {
                public function toResponse($request)
                {
                    return redirect()->route('verification.notice')
                        ->with('success', '会員登録が完了しました。メールアドレスの認証をお願いします。');
                }
            };
        });

        // ログアウト後のリダイレクト先（一般ユーザー・管理者で分岐）
        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse {
                public function toResponse($request)
                {
                    /** @var User|null $user */
                    $user = $request->user();
                    
                    // ログアウト前のユーザー情報で判定
                    if ($user && $user->isAdmin()) {
                        return redirect()->route('admin.login');
                    }
                    
                    return redirect()->route('login');
                }
            };
        });

        // レート制限の設定
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(5)->by($email.$request->ip());
        });

        // 管理者用のカスタムルートを登録（Fortifyは/admin/loginを自動登録しないため）
        $this->registerAdminRoutes();
    }

    /**
     * 管理者用の認証ルートを登録
     *
     * @return void
     */
    protected function registerAdminRoutes()
    {
        // 管理者用ログイン処理（POST /admin/login）
        // FortifyのauthenticateUsingを使用して認証を処理
        Route::post('/admin/login', function (Request $request) {
            // バリデーション（FN016: エラーメッセージ表示）
            // FormRequestを使用してバリデーション（FormRequest + トレイトの併用）
            $formRequest = AdminLoginRequest::createFrom($request);
            $formRequest->setContainer(app());
            $formRequest->setRedirector(app('redirect'));
            
            // FormRequestのバリデーションを実行（セッションにエラーが保存される）
            $formRequest->validateResolved();

            // 管理者ログイン処理（FN014: ログイン認証機能（管理者））
            $adminRole = Role::where('name', Role::NAME_ADMIN)->first();

            if (!$adminRole) {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            $user = User::with('role')
                ->where('email', $request->email)
                ->where('role_id', $adminRole->id)
                ->first();

            if ($user && Hash::check($request->password, $user->password)) {
                Auth::login($user, $request->filled('remember'));
                return app(LoginResponse::class)->toResponse($request);
            }

            // ログイン失敗時のエラーメッセージ（FN016: 入力情報が誤っている場合）
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません'],
            ]);
        })->middleware(['web', 'guest']);

        // 管理者用ログアウト処理（POST /admin/logout）（FN017: ログアウト機能）
        Route::post('/admin/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            // 管理者は管理者ログイン画面にリダイレクト
            return redirect()->route('admin.login');
        })->middleware(['web', 'auth'])->name('admin.logout');
    }
}

