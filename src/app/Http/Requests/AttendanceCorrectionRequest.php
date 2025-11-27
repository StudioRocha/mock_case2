<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * リクエストの認証を許可するかどうか
     * 自分の勤怠のみ申請可能
     *
     * @return bool
     */
    public function authorize()
    {
        // 勤怠レコードを取得（ルートパラメータから）
        $attendanceId = $this->route('id');
        $attendance = $attendanceId ? Attendance::find($attendanceId) : null;

        // 勤怠レコードが存在し、かつ自分の勤怠である場合のみ許可
        return $attendance && $attendance->user_id === Auth::id();
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules()
    {
        return [
            'clock_in_time' => ['nullable', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i'],
            'break_start_times' => ['nullable', 'array'],
            'break_start_times.*' => ['nullable', 'date_format:H:i'],
            'break_end_times' => ['nullable', 'array'],
            'break_end_times.*' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * カスタムバリデーション
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockInTime = $this->input('clock_in_time');
            $clockOutTime = $this->input('clock_out_time');
            $breakStartTimes = $this->input('break_start_times', []);
            $breakEndTimes = $this->input('break_end_times', []);

            // 勤怠レコードを取得（ルートパラメータから）
            $attendanceId = $this->route('id');
            $attendance = $attendanceId ? Attendance::find($attendanceId) : null;

            // 勤怠レコードが存在しない場合はスキップ
            if (!$attendance) {
                return;
            }

            // 承認待ちの修正申請があるかチェック
            $hasPendingRequest = $attendance->stampCorrectionRequests()
                ->where('status', StampCorrectionRequest::STATUS_PENDING)
                ->exists();

            if ($hasPendingRequest) {
                $validator->errors()->add('pending_request', '承認待ちのため修正はできません。');
            }

            // 日をまたぐ勤怠かどうかを判定（元の勤怠レコードから）
            $isOvernight = $attendance->isOvernight();

            // 出勤時間と退勤時間のバリデーション
            if ($clockInTime && $clockOutTime) {
                $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);

                // 日をまたぐ勤怠の場合、すべてのケースを翌日として扱う
                if ($isOvernight) {
                    // 日をまたぐ場合：
                    // - 退勤時間が出勤時間より小さい場合：正常（翌日として扱う、例：23:00 → 02:00）
                    // - 退勤時間が出勤時間より大きい場合：正常（翌日として扱う、例：23:00 → 23:30）
                    // - 退勤時間 = 出勤時間の場合：正常（24時間勤務として扱う、例：23:00 → 23:00）
                    // すべて正常として扱う（エラーなし）
                } else {
                    // 通常の勤怠：出勤時間 < 退勤時間 でなければエラー
                    if ($clockIn->greaterThanOrEqualTo($clockOut)) {
                        $validator->errors()->add('clock_in_time', '出勤時間もしくは退勤時間が不適切な値です');
                        $validator->errors()->add('clock_out_time', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                }
            }

            // 休憩時間のバリデーション
            foreach ($breakStartTimes as $index => $breakStartTime) {
                $breakEndTime = $breakEndTimes[$index] ?? null;

                if ($breakStartTime && $breakEndTime) {
                    $breakStart = Carbon::createFromFormat('H:i', $breakStartTime);
                    $breakEnd = Carbon::createFromFormat('H:i', $breakEndTime);

                    // 休憩開始時間が休憩終了時間より後
                    if ($breakStart->greaterThanOrEqualTo($breakEnd)) {
                        $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                        $validator->errors()->add("break_end_times.{$index}", '休憩時間が不適切な値です');
                    }

                    // 休憩開始時間が出勤時間より前、または退勤時間より後
                    if ($clockInTime) {
                        $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                        if ($breakStart->lessThan($clockIn)) {
                            $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                        }
                    }

                    if ($clockOutTime) {
                        $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                        // 日をまたぐ勤怠の場合、退勤時間が出勤時間より小さい場合は翌日として扱う
                        // 休憩時間は通常、日をまたがないので、退勤時間より小さい場合は正常（翌日の退勤時間として扱う）
                        if ($isOvernight) {
                            // 日をまたぐ場合：休憩開始時間が退勤時間より大きい場合はエラー
                            // （休憩時間は出勤日の範囲内である必要があるため）
                            if ($breakStart->format('H:i') > $clockOut->format('H:i')) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                        } else {
                            // 通常の勤怠：休憩開始時間が退勤時間より後はエラー
                            if ($breakStart->greaterThan($clockOut)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                        }
                    }

                    // 休憩終了時間が退勤時間より後
                    if ($clockOutTime) {
                        $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                        // 日をまたぐ勤怠の場合、退勤時間が出勤時間より小さい場合は翌日として扱う
                        if ($isOvernight) {
                            // 日をまたぐ場合：休憩終了時間が退勤時間より大きい場合はエラー
                            // （休憩時間は出勤日の範囲内である必要があるため）
                            if ($breakEnd->format('H:i') > $clockOut->format('H:i')) {
                                $validator->errors()->add("break_end_times.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
                            }
                        } else {
                            // 通常の勤怠：休憩終了時間が退勤時間より後はエラー
                            if ($breakEnd->greaterThan($clockOut)) {
                                $validator->errors()->add("break_end_times.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * バリデーションメッセージ
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
            'note.max' => '備考は500文字以内で入力してください',
            'pending_request' => '承認待ちのため修正はできません。',
        ];
    }
}

