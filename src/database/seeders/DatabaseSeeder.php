<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        
        // ロールの初期データを作成
        $this->call(RoleSeeder::class);
        
        // 勤怠ダミーデータを作成
        $this->call(AttendanceDummyDataSeeder::class);
    }
}
