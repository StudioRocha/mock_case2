<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * 管理者ログイン画面を表示
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // 既にログインしている場合は管理者ダッシュボードにリダイレクト
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.attendance.list');
        }

        return view('admin.login');
    }

    /**
     * 管理者ログイン処理（Fortifyの認証メソッドを使用）
     *
     * @param AdminLoginRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(AdminLoginRequest $request)
    {
        // 管理者ロールを取得
        $adminRole = Role::where('name', Role::NAME_ADMIN)->first();

        if (!$adminRole) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません'],
            ]);
        }

        // 管理者ユーザーを取得
        $user = User::with('role')
            ->where('email', $request->email)
            ->where('role_id', $adminRole->id)
            ->first();

        // パスワードチェックとログイン処理
        if ($user && Hash::check($request->password, $user->password)) {
            // Fortifyの認証メソッドを使用してログイン
            Auth::login($user, $request->filled('remember'));

            // ログイン成功後のリダイレクト先（管理者勤怠一覧画面）
            return redirect()->intended(route('admin.attendance.list'));
        }

        // ログイン失敗時のエラーメッセージ
        throw ValidationException::withMessages([
            'email' => ['ログイン情報が登録されていません'],
        ]);
    }

    /**
     * 管理者ログアウト処理（Fortifyの認証メソッドを使用）
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Fortifyの認証メソッドを使用してログアウト
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト後のリダイレクト先（管理者ログイン画面）
        return redirect()->route('admin.login');
    }
}

