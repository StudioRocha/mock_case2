# Route,Controller 一覧

## 概要

勤怠管理アプリのルート定義とコントローラー一覧

---

## Route,Controller 一覧表

| 画面名称                         | パス                                                              | メソッド | ルート先コントローラー                 | アクション                | 認証必須                | 説明                                    |
| -------------------------------- | ----------------------------------------------------------------- | -------- | -------------------------------------- | ------------------------- | ----------------------- | --------------------------------------- |
| 会員登録画面（一般ユーザー）     | /register                                                         | GET      | Fortify（自動登録）                    | registerView()            | 不要                    | 会員登録画面を表示                      |
|                                  | /register                                                         | POST     | Fortify（自動登録）                    | CreateNewUser::create()   | 不要                    | 会員登録処理                            |
| ログイン画面（一般ユーザー）     | /login                                                            | GET      | Fortify（自動登録）                    | loginView()               | 不要                    | ログイン画面を表示                      |
|                                  | /login                                                            | POST     | Fortify（自動登録）                    | authenticateUsing()       | 不要                    | ログイン処理                            |
| 出勤登録画面（一般ユーザー）     | /attendance                                                       | GET      | AttendanceController                   | index()                   | 必要（auth + verified） | 勤怠登録画面を表示                      |
|                                  | /attendance/clock-in                                              | POST     | AttendanceController                   | clockIn()                 | 必要（auth + verified） | 出勤処理                                |
|                                  | /attendance/clock-out                                             | POST     | AttendanceController                   | clockOut()                | 必要（auth + verified） | 退勤処理                                |
|                                  | /attendance/break-start                                           | POST     | AttendanceController                   | breakStart()              | 必要（auth + verified） | 休憩開始処理                            |
|                                  | /attendance/break-end                                             | POST     | AttendanceController                   | breakEnd()                | 必要（auth + verified） | 休憩終了処理                            |
| 勤怠一覧画面（一般ユーザー）     | /attendance/list                                                  | GET      | AttendanceController                   | list()                    | 必要（auth + verified） | 勤怠一覧画面を表示                      |
| 勤怠詳細画面（一般ユーザー）     | /attendance/detail/{id}                                           | GET      | AttendanceController                   | detail()                  | 必要（auth + verified） | 勤怠詳細画面を表示                      |
|                                  | /attendance/detail/{id}/correction-request                        | POST     | AttendanceController                   | correctionRequest()       | 必要（auth + verified） | 修正申請処理                            |
| 申請一覧画面（一般ユーザー）     | /stamp_correction_request/list                                    | GET      | CorrectionRequestController            | list()                    | 必要（auth + verified） | 申請一覧画面を表示                      |
| ログイン画面（管理者）           | /admin/login                                                      | GET      | web.php                                | -                         | 不要                    | 管理者ログイン画面を表示                |
|                                  | /admin/login                                                      | POST     | FortifyServiceProvider                 | authenticateUsing()       | 不要                    | 管理者ログイン処理                      |
| 勤怠一覧画面（管理者）           | /admin/attendance/list                                            | GET      | Admin\AttendanceController             | list()                    | 必要（auth + admin）    | 勤怠一覧画面を表示（日次）              |
| 勤怠詳細画面（管理者）           | /admin/attendance/{id}                                            | GET      | Admin\AttendanceController             | show()                    | 必要（auth + admin）    | 勤怠詳細画面を表示                      |
|                                  | /admin/attendance/{id}                                            | PUT      | Admin\AttendanceController             | update()                  | 必要（auth + admin）    | 勤怠情報を更新                          |
| スタッフ一覧画面（管理者）       | /admin/staff/list                                                 | GET      | Admin\StaffController                  | list()                    | 必要（auth + admin）    | スタッフ一覧画面を表示                  |
| スタッフ別勤怠一覧画面（管理者） | /admin/attendance/staff/{id}                                      | GET      | Admin\AttendanceController             | monthly()                 | 必要（auth + admin）    | スタッフ別勤怠一覧画面を表示（月次）    |
|                                  | /admin/attendance/staff/{id}/csv                                  | GET      | Admin\AttendanceController             | exportMonthlyAttendance() | 必要（auth + admin）    | スタッフ別月次勤怠データを CSV 出力     |
| 申請一覧画面（管理者）           | /stamp_correction_request/list                                    | GET      | Admin\StampCorrectionRequestController | list()                    | 必要（auth + admin）    | 申請一覧画面を表示（承認待ち/承認済み） |
| 修正申請承認画面（管理者）       | /stamp_correction_request/approve/{attendance_correct_request_id} | GET      | Admin\StampCorrectionRequestController | show()                    | 必要（auth + admin）    | 修正申請詳細画面を表示                  |
|                                  | /stamp_correction_request/approve/{attendance_correct_request_id} | POST     | Admin\StampCorrectionRequestController | approve()                 | 必要（auth + admin）    | 修正申請を承認                          |

---

## 認証ミドルウェアの説明

### 一般ユーザー向け画面

-   **auth + verified**: 認証済みかつメール認証完了が必要
    -   `auth`: ログイン認証が必要
    -   `verified`: メールアドレスの認証が完了している必要がある

### 管理者向け画面

-   **auth + admin**: 認証済みかつ管理者権限が必要
    -   `auth`: ログイン認証が必要
    -   `admin`: 管理者ロール（`role.name = 'admin'`）が必要

### 認証不要な画面

-   **不要**: ログインしていなくてもアクセス可能

---

## 補足事項

### URL パラメータ（オプション）

以下の画面では、URL パスにオプションパラメータが含まれます：

-   **勤怠一覧画面（一般ユーザー）**: `/attendance/list/{year?}/{month?}` - 年月の指定が可能
-   **勤怠一覧画面（管理者）**: `/admin/attendance/list/{year?}/{month?}/{day?}` - 年月日の指定が可能
-   **スタッフ別勤怠一覧画面（管理者）**: `/admin/attendance/staff/{id}/{year?}/{month?}` - 年月の指定が可能

### パスの重複

-   **申請一覧画面（一般ユーザー）**と**申請一覧画面（管理者）**は同じパス `/stamp_correction_request/list` を使用
-   `CorrectionRequestController`がユーザーのロールを判定し、管理者の場合は`Admin\StampCorrectionRequestController`に委譲

---

## 実装詳細

### Fortify による自動登録ルート

-   `/register` (GET, POST): 会員登録画面・登録処理
-   `/login` (GET, POST): ログイン画面・ログイン処理
-   `/logout` (POST): ログアウト処理

これらのルートは Laravel Fortify が自動的に登録します。

### 管理者ログイン

-   GET `/admin/login`: `web.php`で定義（ビュー表示）
-   POST `/admin/login`: `FortifyServiceProvider`で定義（ログイン処理）
