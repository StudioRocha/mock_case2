<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
        return view('admin.staff.list');
    }
}

