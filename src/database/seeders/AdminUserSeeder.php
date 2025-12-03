<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * 管理者ユーザーのダミーアカウントを作成
     *
     * @return void
     */
    public function run()
    {
        // 管理者ロールを取得
        $adminRole = Role::where('name', Role::NAME_ADMIN)->firstOrFail();

        // 管理者ユーザーを作成
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '管理者',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->command->info("✓ 管理者ユーザーを作成しました: {$user->name} ({$user->email})");
        } else {
            $this->command->line("管理者ユーザーは既に存在します: {$user->name} ({$user->email})");
        }

        $this->command->info('管理者ユーザーのシーディングが完了しました。');
    }
}

