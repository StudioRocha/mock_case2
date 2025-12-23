<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Auth\EmailVerificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ============================================
// 一般ユーザー向け認証（Fortifyを使用）
// ============================================

// ルートパス: ログイン画面にリダイレクト
Route::get('/', function () {
    return redirect()->route('login');
});

// Fortifyが自動的に以下のルートを登録します：
// GET  /login  - ログイン画面
// POST /login  - ログイン処理
// GET  /register - 会員登録画面
// POST /register - 会員登録処理
// POST /logout - ログアウト処理
// ルート名: login, register, logout

// ============================================
// メール認証機能（FN011）
// ============================================
Route::get('/email/verify', function () {
    // 既に認証済みの場合は勤怠登録画面にリダイレクト
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if ($user && $user->hasVerifiedEmail()) {
        return redirect()->route('attendance');
    }
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::get('/email/verify/complete', function () {
    // 認証済みでない場合は認証誘導画面にリダイレクト
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if (!$user || !$user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }
    return view('auth.verification-complete');
})->middleware('auth')->name('verification.complete');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::get('/api/email/verification/status', [EmailVerificationController::class, 'checkStatus'])
    ->middleware('auth')
    ->name('verification.check-status');

// ============================================
// 一般ユーザー向け勤怠機能
// ============================================

// 認証が必要なルートをグループ化（メール認証も必要）
Route::middleware(['auth', 'verified'])->group(function () {
    // PG03: 勤怠登録画面
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');

    // PG04: 勤怠一覧画面
    Route::get('/attendance/list/{year?}/{month?}', [AttendanceController::class, 'list'])->name('attendance.list');

    // PG05: 勤怠詳細画面
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}/correction-request', [AttendanceController::class, 'correctionRequest'])->name('attendance.correction-request');

    // PG06: 申請一覧画面（一般ユーザー）
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'list'])->name('stamp_correction_request.list');
});

// ============================================
// 管理者向け認証（Fortifyを使用）
// ============================================

// PG07: ログイン画面（管理者）
// 管理者用のログイン画面は手動でルーティング（Fortifyは/admin/loginを自動登録しないため）
Route::prefix('admin')->group(function () {
    // GET: 管理者ログイン画面表示
    Route::get('/login', function () {
        // 既にログインしている場合は管理者ダッシュボードにリダイレクト
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.attendance.list');
        }
        return view('admin.login');
    })->name('admin.login');
    
    // POST: 管理者ログイン処理（Fortifyが自動処理）
    // Fortifyが自動的に /admin/login のPOSTリクエストを処理
    // ただし、Fortifyのデフォルトは /login なので、カスタムルートが必要
    // ここではFortifyのauthenticateUsingでリクエストパスを判定して処理
    
    // POST: 管理者ログアウト処理（Fortifyが自動処理）
    // Fortifyが自動的に /admin/logout のPOSTリクエストを処理
});

// ============================================
// 管理者向け機能
// ============================================

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // PG08: 勤怠一覧画面（管理者）- 日次勤怠一覧
    Route::get('/attendance/list/{year?}/{month?}/{day?}', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');

    // PG09: 勤怠詳細画面（管理者）
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // PG10: スタッフ一覧画面
    Route::get('/staff/list', [\App\Http\Controllers\Admin\StaffController::class, 'list'])->name('admin.staff.list');

    // PG11: スタッフ別勤怠一覧画面（月次）
    Route::get('/attendance/staff/{id}/{year?}/{month?}', [AdminAttendanceController::class, 'monthly'])->name('admin.attendance.monthly');
    
    // PG11: スタッフ別勤怠データCSV出力（FN045: CSV出力機能）
    Route::get('/attendance/staff/{id}/csv/{year?}/{month?}', [AdminAttendanceController::class, 'exportMonthlyAttendance'])->name('admin.attendance.monthly.csv');

});

// ============================================
// 管理者向け申請機能（/admin プレフィックスなし）
// ============================================

// PG13: 修正申請承認画面（管理者）
// 仕様書: /stamp_correction_request/approve/{attendance_correct_request_id}
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'show'])->name('admin.stamp_correction_request.approve');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'approve'])->name('admin.stamp_correction_request.approve.post');
});
