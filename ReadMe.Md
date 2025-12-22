# 模擬案件\_フリマアプリ

## 環境構築

**Docker ビルド**

1. `git@github.com:StudioRocha/mock_case1.git`
2. `docker-compose up -d --build`

※補足: MySQL は OS によって起動しない場合があるため、各 PC に合わせて docker-compose.yml を編集してください。

**Laravel 環境構築**

1. コマンドライン上

```bash
 docker-compose exec php bash
```

2. PHP コンテナ上

```bash
 composer install
```

3. .env.example から .env を作成し、環境変数を設定

4. .env に以下の環境変数を設定、又は追加
   <BR>

<span style="color: red; font-size: 12px;">※応用要件の PhpUnit を用いたテストケースを実施する場合は STRIPE_PUBLIC_KEY/STRIPE_SECRET_KEY の値を自身の stripe アカウントのダッシュボードから取得して設定。</span>

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# ===========================================
# メール認証設定/応用要件
# ===========================================
# AUTO_VERIFY_ENABLED=true//"MailHog経由でコード入力なしで承認"ボタンをアプリ内に表示（開発環境用）
# false: "MailHog経由/コード入力なしで承認"ボタンを非表示
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
AUTO_VERIFY_ENABLED=true

# ===========================================
# Stripe設定/応用要件
# ===========================================
# 開発環境ではwebhookは未使用のためSTRIPE_WEBHOOK_SECRETの値はそのまま。

STRIPE_PUBLIC_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET_KEY=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
STRIPE_ENVIRONMENT=test
STRIPE_WEBHOOK_ENABLED=false

```

5. アプリケーションキーの作成
   <BR> PHP コンテナ上

```bash
php artisan key:generate
```

6. マイグレーションの実行
   <BR> PHP コンテナ上

```bash
php artisan migrate
```

7. 全キャッシュクリア（一括）
   <BR> PHP コンテナ上

```bash
php artisan optimize:clear

```

8. ストレージリンク作成<BR>
   <BR> PHP コンテナ上

```bash
php artisan storage:link

```

9. ブラウザで開発環境に接続

http://localhost/

もし接続時に UnexpectedValueException ページエラーが出た場合。<BR>
![Warning](https://img.shields.io/badge/Warning-Permission%20denied-red)

Windows WSL 環境で http://localhost/　接続時にファイル権限のエラーが出る場合があります。
"Permission denied"というエラーが発生した場合は、
このプロジェクトの root ディレクトリで下記のコマンドを実行してパーミッションを変更後に再接続。

```bash
sudo chmod -R 777 src/*
```

10. シーディングの実行(指定されたダミーデータの一括生成)

```bash
 php artisan db:seed
```

### 生成されるデータ

#### カテゴリデータ

-   **テーブル**: `categories`
-   **データ数**:14 件
-   **内容**:
    -   'ファッション','家電','インテリア',
        'レディース','メンズ','コスメ','本',
        'ゲーム','スポーツ','キッチン','ハンドメイド',
        'アクセサリー','おもちゃ','ベビー・キッズ'

#### 商品データ

-   **テーブル**: `items`
-   **データ数**: 10 件
-   **内容**:
    -   固定商品 10 点を事前定義
    -   各商品に 1-3 個のカテゴリをランダム割り当て
    -   外部画像をローカルにダウンロードして保存
    -   サンプル商品例：
        -   腕時計 (¥15,000) - Rolax
        -   HDD (¥5,000) - 西芝
        -   ノート PC (¥45,000)
        -   コーヒーミル (¥4,000) - Starbacks
        -   メイクセット (¥2,500)

#### ユーザーデータ

-   **テーブル**: `users`
-   **データ数**: 11 件
-   **内容**:
    -   固定ユーザー: dev@example.com（パスワード: password）
    -   ランダムユーザー: 10 件（パスワード: password）
    -   全ユーザーがメール認証済み状態

#### プロフィールデータ

-   **テーブル**: `profiles`
-   **データ数**: 11 件
-   **内容**:
    -   各ユーザーに対応するプロフィール情報
    -   住所

### 注意事項

-   シーディングは開発・テスト環境でのみ実行してください
-   本番環境では実行しないでください
-   既存データがある場合は、事前にバックアップを取ってください
-   商品画像は外部 URL から自動ダウンロードされます

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

1. **RegisterFeatureTest.php** - 会員登録機能（テスト ID: 1）
2. **LoginFeatureTest.php** - ログイン機能（テスト ID: 2）
3. **LogoutFeatureTest.php** - ログアウト機能（テスト ID: 3）
4. **ItemListFeatureTest.php** - 商品一覧機能（テスト ID: 4）
5. **MyListFeatureTest.php** - マイリスト機能（テスト ID: 5）
6. **ItemSearchFeatureTest.php** - 商品検索機能（テスト ID: 6）
7. **ItemDetailFeatureTest.php** - 商品詳細表示機能（テスト ID: 7）
8. **ItemLikeFeatureTest.php** - 商品いいね機能（テスト ID: 8）
9. **ItemCommentFeatureTest.php** - 商品コメント機能（テスト ID: 9）
10. **ItemPurchaseFeatureTest.php** - 商品購入機能（テスト ID: 10）
11. **PaymentMethodSelectionFeatureTest.php** - 支払い方法選択機能（テスト ID: 11）
12. **AddressChangeFeatureTest.php** - 住所変更機能（テスト ID: 12）
13. **UserProfileFeatureTest.php** - ユーザープロフィール表示機能（テスト ID: 13）
14. **UserProfileUpdateFeatureTest.php** - ユーザープロフィール更新機能（テスト ID: 14）
15. **ItemRegistrationFeatureTest.php** - 商品出品機能（テスト ID: 15）
16. **EmailVerificationFeatureTest.php** - メール認証機能（テスト ID: 16）

### 注意事項

-   上記コマンドは PHP コンテナ内で実行してください
-   テスト用データベース（`laravel_test_db`）は本番データベース（`laravel_db`）とは別です
-   `RefreshDatabase`トレイトにより、テスト実行時に自動的にデータベースがリフレッシュされます

<BR>

## 使用技術(実行環境)

-   php:8.1-fpm
-   Laravel 8.75
-   Laravel Fortify (認証パッケージ)
-   MySQL 8.0.26
-   mailhog

## 外部サービス・API

-   Stripe　18.0

**決済処理**: Stripe Checkout API を使用

#### 設定情報

-   **環境**: テスト環境（test mode）
-   **通貨**: JPY（日本円）
-   **決済方法**:
    -   クレジットカード決済
    -   コンビニ決済（Konbini）

#### API 設定ファイル

-   **設定ファイル**: `src/config/stripe.php`
-   **サービスクラス**: `src/app/Services/StripeService.php`

**注意**: `STRIPE_PUBLIC_KEY`と`STRIPE_SECRET_KEY`にはStripe アカウントのAPI キーを入力してください。

-   Stripe ダッシュボード → 開発者 → API キー から取得可能
-   テスト環境では `pk_test_` と `sk_test_` で始まるキーを使用 -　　開発環境では　 webhook は未使用

#### 決済フロー

1. **決済セッション作成**: `StripeService::createCheckoutSession()`
2. **Stripe Checkout 画面**: ユーザーが決済情報を入力
3. **決済完了**: `stripe.success`ルートで処理
4. **決済キャンセル**: `items.show`ルートに戻る

#### テスト用カード情報

-   **成功**: 4242 4242 4242 4242
-   **失敗**: 4000 0000 0000 0002

#### 実装詳細

-   **モック化**: テスト時は StripeService をモック化

## ER 図

<img src="docs/images/ER.svg" width="800" alt="ER図">

ソース（編集用）: [docs/ER.dio]

## URL

-   開発環境：http://localhost/
-   phpMyAdmin:：http://localhost:8080/
-   MailHog ：http://localhost:8025/
