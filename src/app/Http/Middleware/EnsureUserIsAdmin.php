<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 認証されていない場合はログイン画面にリダイレクト
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        // 管理者でない場合は403エラーまたはログイン画面にリダイレクト
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, '管理者権限が必要です');
        }

        return $next($request);
    }
}

