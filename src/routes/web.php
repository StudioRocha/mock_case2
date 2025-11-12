<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminStampCorrectionRequestController;

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

Route::get('/', function () {
    return view('welcome');
});

// ============================================
// 一般ユーザー向け認証
// ============================================

// PG01: 会員登録画面
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// PG02: ログイン画面（一般ユーザー）
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ============================================
// 一般ユーザー向け勤怠機能
// ============================================

// PG03: 勤怠登録画面
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');

// PG04: 勤怠一覧画面
Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

// PG05: 勤怠詳細画面
Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');

// PG06: 申請一覧画面（一般ユーザー）
Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'list'])->name('stamp_correction_request.list');
Route::get('/stamp_correction_request/create', [StampCorrectionRequestController::class, 'create'])->name('stamp_correction_request.create');
Route::post('/stamp_correction_request/store', [StampCorrectionRequestController::class, 'store'])->name('stamp_correction_request.store');

// ============================================
// 管理者向け認証
// ============================================

// PG07: ログイン画面（管理者）
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
});

// ============================================
// 管理者向け機能
// ============================================

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // PG08: 勤怠一覧画面（管理者）
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');

    // PG09: 勤怠詳細画面（管理者）
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');

    // PG10: スタッフ一覧画面
    Route::get('/staff/list', [StaffController::class, 'list'])->name('admin.staff.list');

    // PG11: スタッフ別勤怠一覧画面
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staff'])->name('admin.attendance.staff');

    // PG12: 申請一覧画面（管理者）
    Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'list'])->name('admin.stamp_correction_request.list');

    // PG13: 修正申請承認画面
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminStampCorrectionRequestController::class, 'approve'])->name('admin.stamp_correction_request.approve');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminStampCorrectionRequestController::class, 'processApprove']);
});

// ============================================
// 申請一覧画面（PG06/PG12共通パス）
// 認証ミドルウェアでロール判定して画面切り替え
// ============================================
// 注意: PG06とPG12は同じパス /stamp_correction_request/list を使用
// 上記で個別に定義済み（一般ユーザー用と管理者用）
