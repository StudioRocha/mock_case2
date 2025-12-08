<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use App\Models\BreakCorrection;
use App\Models\BreakTime;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    /**
     * 日付フォーマット定数
     */
    private const DATE_FORMAT = 'Y/m/d';

    /**
     * 申請一覧画面（管理者）を表示（FN047, FN048: 承認待ち/承認済み情報取得機能）
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function list(Request $request)
    {
        // タブの状態を取得（デフォルトは承認待ち）
        $status = $request->get('status', 'pending');
        
        // 一般ユーザーのロールを取得
        $userRole = Role::where('name', Role::NAME_USER)->first();
        
        // 全一般ユーザーの申請を取得（attendance経由で一般ユーザーのみフィルタリング）
        $query = StampCorrectionRequest::whereHas('attendance.user', function ($q) use ($userRole) {
                $q->where('role_id', $userRole->id);
            })
            ->with(['attendance.user'])
            ->orderBy('created_at', 'desc');
        
        // ステータスでフィルタリング
        if ($status === 'pending') {
            // FN047: 承認待ち情報取得機能
            $query->where('status', StampCorrectionRequest::STATUS_PENDING);
        } elseif ($status === 'approved') {
            // FN048: 承認済み情報取得機能
            $query->where('status', StampCorrectionRequest::STATUS_APPROVED);
        }
        
        $requests = $query->paginate(10);
        
        // 各リクエストにフォーマット済みの値を追加
        $requests->getCollection()->transform(function ($request) {
            $request->formatted_attendance_date = $request->attendance->date->format(self::DATE_FORMAT);
            $request->formatted_created_at = $request->created_at->format(self::DATE_FORMAT);
            return $request;
        });
        
        return view('admin.stamp_correction_request.list', [
            'requests' => $requests,
            'currentStatus' => $status,
        ]);
    }

    /**
     * 修正申請詳細画面を表示（FN050: 申請詳細取得機能）
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // 修正申請レコードを取得
        $stampCorrectionRequest = StampCorrectionRequest::with([
            'attendance.user',
            'breakCorrections'
        ])->findOrFail($id);

        // 修正申請の内容（requested_*）を表示（FN050に準拠）
        // 出勤・退勤時刻のフォーマット
        $displayClockInTime = $stampCorrectionRequest->requested_clock_in_time
            ? $stampCorrectionRequest->requested_clock_in_time->format('H:i')
            : '';
        $displayClockOutTime = $stampCorrectionRequest->requested_clock_out_time
            ? $stampCorrectionRequest->requested_clock_out_time->format('H:i')
            : '';

        // 休憩時間の詳細を準備
        $breakDetails = [];
        foreach ($stampCorrectionRequest->breakCorrections as $breakCorrection) {
            $startTime = $breakCorrection->requested_break_start_time
                ? $breakCorrection->requested_break_start_time->format('H:i')
                : '';
            $endTime = $breakCorrection->requested_break_end_time
                ? $breakCorrection->requested_break_end_time->format('H:i')
                : '';
            
            $breakDetails[] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        // 有効な休憩の数をカウント（最後の空白休憩を除く）
        $validBreakCount = 0;
        foreach ($breakDetails as $break) {
            $startTime = $break['start_time'] ?? '';
            $endTime = $break['end_time'] ?? '';
            if (!empty($startTime) && !empty($endTime) && $startTime !== '-' && $endTime !== '-') {
                $validBreakCount++;
            }
        }

        // 各休憩に表示用の情報を追加
        $processedBreakDetails = [];
        foreach ($breakDetails as $index => $break) {
            $startTime = $break['start_time'] ?? '';
            $endTime = $break['end_time'] ?? '';
            
            // 有効な休憩かどうか（開始時間と終了時間の両方が存在する場合）
            $hasValidBreak = !empty($startTime) && !empty($endTime) && $startTime !== '-' && $endTime !== '-';
            
            // 最後の要素（修正用の空白休憩）かどうかを判定
            $isLastBreak = $index === count($breakDetails) - 1;
            
            // 有効な休憩、または最後の空白休憩の場合は表示
            $shouldDisplay = $hasValidBreak || $isLastBreak;
            
            // 表示する休憩の番号を決定
            $breakNumber = $hasValidBreak ? ($index + 1) : ($validBreakCount + 1);
            
            $processedBreakDetails[] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'has_valid_break' => $hasValidBreak,
                'is_last_break' => $isLastBreak,
                'should_display' => $shouldDisplay,
                'break_number' => $breakNumber,
            ];
        }

        // 承認可能かどうか（承認待ちの場合のみ承認可能）
        $canApprove = $stampCorrectionRequest->status === StampCorrectionRequest::STATUS_PENDING;

        return view('admin.stamp_correction_request.detail', [
            'stampCorrectionRequest' => $stampCorrectionRequest,
            'displayClockInTime' => $displayClockInTime,
            'displayClockOutTime' => $displayClockOutTime,
            'displayNote' => $stampCorrectionRequest->requested_note ?? '',
            'breakDetails' => $processedBreakDetails,
            'canApprove' => $canApprove,
        ]);
    }

    /**
     * 修正申請を承認（FN051: 承認機能）
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processApprove($id)
    {
        // 修正申請レコードを取得
        $stampCorrectionRequest = StampCorrectionRequest::with([
            'attendance',
            'breakCorrections'
        ])->findOrFail($id);

        // 承認待ちでない場合はエラー
        if ($stampCorrectionRequest->status !== StampCorrectionRequest::STATUS_PENDING) {
            return redirect()->route('admin.stamp_correction_request.detail', ['id' => $id])
                ->with('error', 'この申請は既に処理済みです。');
        }

        DB::beginTransaction();
        try {
            // 勤怠レコードを取得
            $attendance = $stampCorrectionRequest->attendance;

            // 1. 出勤時刻の更新
            $attendance->clock_in_time = $stampCorrectionRequest->requested_clock_in_time;
            
            // 2. 退勤時刻の更新
            $attendance->clock_out_time = $stampCorrectionRequest->requested_clock_out_time;
            
            // 3. 備考の更新
            $attendance->note = $stampCorrectionRequest->requested_note;
            
            $attendance->save();

            // 4. 既存の休憩レコードを削除
            $attendance->breaks()->delete();

            // 5. 新しい休憩レコードを作成（break_correctionsから）
            foreach ($stampCorrectionRequest->breakCorrections as $breakCorrection) {
                if ($breakCorrection->requested_break_start_time && $breakCorrection->requested_break_end_time) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start_time' => $breakCorrection->requested_break_start_time,
                        'break_end_time' => $breakCorrection->requested_break_end_time,
                    ]);
                }
            }

            // 6. 修正申請のステータスを承認済みに更新
            $stampCorrectionRequest->update([
                'status' => StampCorrectionRequest::STATUS_APPROVED,
                'approved_at' => Carbon::now(),
            ]);

            DB::commit();

            // 承認後は詳細画面にリダイレクト（承認済みとして表示）
            return redirect()->route('admin.stamp_correction_request.detail', ['id' => $id])
                ->with('success', '修正申請を承認しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.stamp_correction_request.detail', ['id' => $id])
                ->with('error', '承認処理中にエラーが発生しました。');
        }
    }
}

