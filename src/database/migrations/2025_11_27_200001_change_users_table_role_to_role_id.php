<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeUsersTableRoleToRoleId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 既存のroleカラムの値を保持するために一時カラムを作成
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_temp')->nullable()->after('role');
        });

        // 既存のrole値をrole_tempにコピー
        DB::statement('UPDATE users SET role_temp = role');

        // 元のroleカラムを削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // role_idカラムを追加（外部キー制約付き）
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->after('password');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });

        // role_tempの値に基づいてrole_idを設定
        // 既存データの移行: role=0 → role_id=1(user), role=1 → role_id=2(admin)
        $userRole = DB::table('roles')->where('name', 'user')->first();
        $adminRole = DB::table('roles')->where('name', 'admin')->first();

        if ($userRole && $adminRole) {
            DB::table('users')->where('role_temp', 0)->update(['role_id' => $userRole->id]);
            DB::table('users')->where('role_temp', 1)->update(['role_id' => $adminRole->id]);
        }

        // 一時カラムを削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_temp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 一時カラムを作成
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_temp')->nullable()->after('role_id');
        });

        // role_idの値に基づいてrole_tempに値を設定
        $userRole = DB::table('roles')->where('name', 'user')->first();
        $adminRole = DB::table('roles')->where('name', 'admin')->first();

        if ($userRole && $adminRole) {
            DB::table('users')->where('role_id', $userRole->id)->update(['role_temp' => 0]);
            DB::table('users')->where('role_id', $adminRole->id)->update(['role_temp' => 1]);
        }

        // 外部キー制約を削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        // 元のroleカラムを復元
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role')->after('password');
        });

        // role_tempの値をroleにコピー
        DB::statement('UPDATE users SET role = role_temp');

        // 一時カラムを削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_temp');
        });
    }
}

