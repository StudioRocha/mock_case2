<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication {
        createApplication as baseCreateApplication;
    }

    /**
     * アプリケーションを作成する前に、テスト用データベースを強制的に設定
     * これにより、RefreshDatabaseトレイトが正しいデータベースを使用する
     */
    public function createApplication()
    {
        // phpunit.xmlの設定を優先的に使用（$_SERVERから直接取得）
        // これにより.envファイルの設定が優先されることを防ぐ
        $testDatabase = $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'laravel_test_db';
        
        // 開発用データベース（laravel_db）が使用されないように保護
        if ($testDatabase === 'laravel_db') {
            throw new \RuntimeException(
                'テスト実行時は開発用データベース（laravel_db）を使用できません。' .
                'phpunit.xmlでDB_DATABASE=laravel_test_dbが設定されているか確認してください。'
            );
        }

        // アプリケーション作成前に環境変数を設定
        // これにより、config/database.phpが読み込まれる時点で正しいデータベース名が使用される
        $_ENV['DB_DATABASE'] = $testDatabase;
        putenv("DB_DATABASE={$testDatabase}");

        // アプリケーションを作成（この時点でconfig/database.phpが読み込まれる）
        $app = $this->baseCreateApplication();

        // データベース接続設定をテスト用に強制設定（念のため再設定）
        // RefreshDatabaseトレイトがこの設定を使用してリセットする
        Config::set('database.connections.mysql.database', $testDatabase);
        
        // デフォルト接続もテスト用データベースに設定
        Config::set('database.default', 'mysql');

        return $app;
    }
}
