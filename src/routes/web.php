<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
// use App\Http\Controllers\Admin\StaffController; // TODO: コントローラー作成後に有効化
// use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController; // TODO: コントローラー作成後に有効化

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
// 一般ユーザー向け勤怠機能
// ============================================

// 認証が必要なルートをグループ化
Route::middleware(['auth'])->group(function () {
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
    Route::get('/attendance/staff/{id}/{year?}/{month?}', [AdminAttendanceController::class, 'staff'])->name('admin.attendance.staff');

    // PG12: 申請一覧画面（管理者）
    Route::get('/stamp_correction_request/list', [\App\Http\Controllers\Admin\StampCorrectionRequestController::class, 'list'])->name('admin.stamp_correction_request.list');

    // PG13: 修正申請承認画面
    // Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'approve'])->name('admin.stamp_correction_request.approve');
    // Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'processApprove']);
});

// ============================================
// 申請一覧画面（PG06/PG12共通パス）
// 認証ミドルウェアでロール判定して画面切り替え
// ============================================
// 注意: PG06とPG12は同じパス /stamp_correction_request/list を使用
// 上記で個別に定義済み（一般ユーザー用と管理者用）
