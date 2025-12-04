<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class StampCorrectionRequestController extends Controller
{
    /**
     * 申請一覧画面（管理者）を表示
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
        return view('admin.stamp_correction_request.list');
    }
}

