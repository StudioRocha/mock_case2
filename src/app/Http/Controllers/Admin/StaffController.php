<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示（FN041: ユーザー情報取得機能）
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
        // 一般ユーザーのロールを取得
        $userRole = Role::where('name', Role::NAME_USER)->first();

        // 全一般ユーザーを取得（氏名、メールアドレス）
        $staffs = User::where('role_id', $userRole->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.staff.list', [
            'staffs' => $staffs,
        ]);
    }
}

