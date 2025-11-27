<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
    /**
     * 日付フォーマット定数
     */
    private const DATE_FORMAT = 'Y/m/d';

    /**
     * 申請一覧画面を表示
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function list(Request $request)
    {
        // タブの状態を取得（デフォルトは承認待ち）
        $status = $request->get('status', 'pending');
        
        // 現在のユーザーの申請を取得（attendance経由でフィルタリング）
        $query = StampCorrectionRequest::whereHas('attendance', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['attendance.user'])
            ->orderBy('created_at', 'desc');
        
        // ステータスでフィルタリング
        if ($status === 'pending') {
            $query->where('status', StampCorrectionRequest::STATUS_PENDING);
        } elseif ($status === 'approved') {
            $query->where('status', StampCorrectionRequest::STATUS_APPROVED);
        }
        
        $requests = $query->paginate(10);
        
        // 各リクエストにフォーマット済みの値を追加
        $requests->getCollection()->transform(function ($request) {
         
            $request->formatted_attendance_date = $request->attendance->date->format(self::DATE_FORMAT);
            $request->formatted_created_at = $request->created_at->format(self::DATE_FORMAT);
            return $request;
        });
        
        return view('stamp_correction_request.list', [
            'requests' => $requests,
            'currentStatus' => $status,
        ]);
    }
}

