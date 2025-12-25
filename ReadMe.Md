# 新模擬案件 2 　/模擬案件\_勤怠管理アプリ

## 環境構築

**Docker ビルド**

1. `git@github.com:StudioRocha/mock_case2.git`
2. `docker-compose up -d --build`

※補足: MySQL は OS によって起動しない場合があるため、各 PC に合わせて docker-compose.yml を編集してください。
※補足: この ReadMe は基本的には WindowsOS での環境構築を記したものです。
**Laravel 環境構築**

### 1. コマンドライン上

```bash
 docker-compose exec php bash
```

### 2. PHP コンテナ上

```bash
 composer install
```

### 3. .env.example から .env を作成し、以下の環境変数を設定または追加

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# ===========================================
# メール認証設定/応用要件設定用
# ===========================================
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="CT COACHTECH"


```

### 4. アプリケーションキーの作成

PHP コンテナ上

```bash
php artisan key:generate
```

### 5. マイグレーションの実行

PHP コンテナ上

```bash
php artisan migrate
```

### 6. 全キャッシュクリア（一括）

PHP コンテナ上

```bash
php artisan optimize:clear
```

### 7. ブラウザで開発環境に接続<span style="color: red;">(※WindowsWSL 環境では接続するには下記のパーミッション変更が必要です )</span>

http://localhost/

接続時に UnexpectedValueException ページエラーが出た場合。<BR>
![Warning](https://img.shields.io/badge/Warning-Permission%20denied-red)

Windows WSL 環境で http://localhost/　接続時にファイル権限のエラーが出る場合があります。
"Permission denied"というエラーが発生した場合は、
このプロジェクトの root ディレクトリで下記のコマンドを実行してパーミッションを変更後に再接続。

```bash
sudo chmod -R 777 src/*
```

### 9. シーディングの実行(ダミーデータの一括生成)

```bash
 php artisan db:seed
```

### 生成されるデータ

#### ロールデータ

-   **テーブル**: `roles`
-   **データ数**: 2 件
-   **内容**:
    -   `user`（一般ユーザー）
    -   `admin`（管理者）
    -   マイグレーション実行時に自動生成

#### 管理者ユーザーデータ

-   **テーブル**: `users`
-   **データ数**: 1 件
-   **内容**:
    -   管理者ユーザー: admin@example.com（パスワード: password）
    -   名前: 管理者
    -   ロール: 管理者（admin）
    -   メール認証済み状態

#### 一般ユーザーデータ

-   **テーブル**: `users`
-   **データ数**: 6 件
-   **内容**:
    -   固定ユーザー 6 名（パスワード: password）
    -   ユーザー一覧:
        -   山田太郎 (yamada@example.com)
        -   佐藤花子 (sato@example.com)
        -   鈴木一郎 (suzuki@example.com)
        -   田中次郎 (tanaka@example.com)
        -   伊藤三郎 (ito@example.com)
        -   渡辺四郎 (watanabe@example.com)
    -   全ユーザーがメール認証済み状態

#### 勤怠データ

-   **テーブル**: `attendances`
-   **データ数**: 約 150 件（30 日 × 5 ユーザー）
-   **内容**:
    -   シーディング実行時の前日から 30 日分の勤怠データ（土日を除外）
    -   各日付ごとに、1 人を除いた全ユーザーに勤怠データを登録（必ず 1 人は未登録）
    -   通常勤務パターン: 9:00-17:00、休憩 12:00-13:00
    -   ステータス: 退勤済（STATUS_FINISHED）

#### 休憩データ

-   **テーブル**: `break_times`
-   **データ数**: 約 150 件（各勤怠データに対応）
-   **内容**:
    -   各勤怠データに対応する休憩データ
    -   休憩時間: 12:00-13:00（1 時間）

### 注意事項

-   シーディングは開発・テスト環境でのみ実行してください
-   本番環境では実行しないでください
-   既存データがある場合は、事前にバックアップを取ってください
-   勤怠データは、シーディング実行時の前日から 30 日分（土日を除外）が生成されます
-   各日付ごとに、1 人を除いた全ユーザーに勤怠データを登録するため、必ず 1 人は未登録の状態になります

## テストケースの実施

### テスト用データベースの準備

Feature テストを実行するために、テスト用データベースの準備が必要です。

#### 1. テスト用データベースの作成

MySQL(ROOT) コンテ上で

```bash
CREATE DATABASE laravel_test_db;
```

#### 2. テスト用データベースのマイグレーション

php コンテナ上

```bash
DB_DATABASE=laravel_test_db php artisan migrate:fresh
```

テスト用マイグレーション実行時に Laravel のデータベースクエリ例外エラーが出た場合。<BR>
![Warning](https://img.shields.io/badge/Warning-Illuminate\Database\QueryException-red)

Windows WSL 環境では作成したテスト用の DB への書き込み時にファイル権限のエラーが出る場合があります。
"Permission denied"というエラーが発生した場合は、下記のコマンドを実行して権限を付与してからテスト用 DB マイグレーションを再実行して下さい。

MySQL(ROOT) コンテ上で

```bash
GRANT ALL PRIVILEGES ON laravel_test_db.* TO 'laravel_user'@'%';
FLUSH PRIVILEGES;
```

### 機能テスト実行

php コンテナ上

```bash
php artisan test --testsuite=Feature

```

### Feature テスト一覧：

1. **TestId1RegisterFeatureTest.php** - 会員登録機能（テスト ID: 1）
2. **TestId2LoginFeatureTest.php** - ログイン認証機能（一般ユーザー）（テスト ID: 2）
3. **TestId3AdminLoginFeatureTest.php** - ログイン認証機能（管理者）（テスト ID: 3）
4. **TestId4DateTimeDisplayFeatureTest.php** - 日時取得機能（テスト ID: 4）
5. **TestId5AttendanceStatusFeatureTest.php** - ステータス確認機能（テスト ID: 5）
6. **TestId6ClockInFeatureTest.php** - 出勤機能（テスト ID: 6）
7. **TestId7BreakFeatureTest.php** - 休憩機能（テスト ID: 7）
8. **TestId8ClockOutFeatureTest.php** - 退勤機能（テスト ID: 8）
9. **TestId9AttendanceListFeatureTest.php** - 勤怠一覧情報取得機能（一般ユーザー）（テスト ID: 9）
10. **TestId10AttendanceDetailFeatureTest.php** - 勤怠詳細情報取得機能（一般ユーザー）（テスト ID: 10）
11. **TestId11AttendanceCorrectionFeatureTest.php** - 勤怠詳細情報修正機能（一般ユーザー）（テスト ID: 11）
12. **TestId12AdminAttendanceListFeatureTest.php** - 勤怠一覧情報取得機能（管理者）（テスト ID: 12）
13. **TestId13AdminAttendanceDetailFeatureTest.php** - 勤怠詳細情報取得・修正機能（管理者）（テスト ID: 13）
14. **TestId14AdminUserInfoFeatureTest.php** - ユーザー情報取得機能（管理者）（テスト ID: 14）
15. **TestId15AdminCorrectionRequestFeatureTest.php** - 勤怠情報修正機能（管理者）（テスト ID: 15）
16. **TestId16EmailVerificationFeatureTest.php** - メール認証機能（テスト ID: 16）

### 注意事項

-   上記コマンドは PHP コンテナ内で実行してください
-   テスト用データベース（`laravel_test_db`）は本番データベース（`laravel_db`）とは別です
-   `RefreshDatabase`トレイトにより、テスト実行時に自動的にデータベースがリフレッシュされます

<BR>
## MailHog（メール認証機能）

### メール認証の流れ

1. **会員登録時**: 登録完了後、自動的にメール認証メールが送信されます（MailHog に保存されます）

2. **メール認証方法**:

    **方法 A: メール認証誘導画面から MailHog を開く**

    - `/email/verify` にアクセス（メール認証誘導画面）
    - 「認証はこちらから」ボタンをクリック
    - MailHog の受信箱（`http://localhost:8025/`）が新しいタブで開きます

    **方法 B: MailHog 受信箱から直接認証**

    - `http://localhost:8025/` にアクセス（MailHog 受信箱）
    - 送信されたメール認証メールを開く
    - メール内の「認証してユーザー登録する」ボタンをクリック
    - メール認証が完了し、勤怠登録画面にリダイレクトされます

3. **認証メール再送**: メール認証誘導画面から「認証メールを再送する」ボタンで再送信可能（1 分間に最大 6 回まで）

### 注意事項

-   MailHog は開発環境でのみ使用します（本番環境では使用しません）
-   メール認証リンクは 24 時間有効です
-   メール認証が完了していないと、勤怠機能（出勤・退勤など）は使用できません

<BR>

## 使用技術(実行環境)

-   PHP 8.1-fpm
-   Laravel 8.75
-   Laravel Fortify (認証パッケージ)
-   MySQL 8.0.26
-   MailHog (メール送信テスト用)

## ER 図

<img src="docs/images/attendance-ER.svg" width="1280" alt="ER図">

ソース（編集用）: [docs/attendance-ER.drawio]

## URL

-   開発環境：http://localhost/
-   phpMyAdmin:：http://localhost:8080/
-   MailHog ：http://localhost:8025/
