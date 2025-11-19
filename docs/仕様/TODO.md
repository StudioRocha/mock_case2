# TODO リスト

## データベース・シーダー

### DatabaseSeeder.php に管理者ユーザーを作成

-   [ ] DatabaseSeeder.php に管理者ユーザーを作成するコードを追加

```php
User::create([
    'name' => '管理者',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'role' => User::ROLE_ADMIN,
]);
```

---

## その他のタスク

<!-- ここに追加のTODOタスクを記述してください -->

