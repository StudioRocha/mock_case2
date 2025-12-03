<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * ロールの初期データを作成
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate(
            ['name' => Role::NAME_USER],
            ['label' => '一般ユーザー']
        );

        Role::firstOrCreate(
            ['name' => Role::NAME_ADMIN],
            ['label' => '管理者']
        );

        $this->command->info('ロールの初期データを作成しました。');
    }
}

