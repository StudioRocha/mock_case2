<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * テスト実行前のセットアップ
     * 開発用データベース（laravel_db）がリセットされないように、
     * テスト用データベース（laravel_test_db）を強制的に使用する
     */
    protected function setUp(): void
    {
        parent::setUp();

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

        // データベース接続設定をテスト用に強制設定
        // RefreshDatabaseトレイトがこの設定を使用してリセットする
        Config::set('database.connections.mysql.database', $testDatabase);
        
        // デフォルト接続もテスト用データベースに設定
        Config::set('database.default', 'mysql');
    }
}
