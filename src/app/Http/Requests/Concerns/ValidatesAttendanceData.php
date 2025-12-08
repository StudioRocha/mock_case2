<?php

namespace App\Http\Requests\Concerns;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

trait ValidatesAttendanceData
{
    /**
     * バリデーションルール（共通）
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
     * カスタムバリデーション（共通）
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
                $validator->errors()->add('pending_request', '*承認待ちのため修正はできません。');
            }

            // 元の勤怠レコードが日跨ぎかどうかを判定
            $originalIsOvernight = $attendance->isOvernight();

            // 修正で入力された時刻から日跨ぎかどうかを判定（初期値はfalse）
            $isOvernight = false;
            if ($clockInTime && $clockOutTime) {
                $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                
                // 退勤時間が出勤時間より小さい場合は日跨ぎとして扱う（例：23:00 → 02:00）
                $isOvernight = $clockOut->lessThan($clockIn) || $clockOut->equalTo($clockIn);
            }

            // 出勤時間と退勤時間のバリデーション
            if ($clockInTime && $clockOutTime) {
                $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);

                // 元の勤怠レコードが日跨ぎでない場合（同じ日付内の通常の勤怠）
                if (!$originalIsOvernight) {
                    // 同じ日付内の勤怠の場合、出勤時間 < 退勤時間 でなければエラー
                    if ($clockIn->greaterThanOrEqualTo($clockOut)) {
                        // 1つのエラーメッセージのみを表示するため、カスタムキーを使用
                        $validator->errors()->add('clock_time', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                } else {
                    // 元の勤怠レコードが日跨ぎの場合、修正でも日跨ぎを許可
                    // 日をまたぐ勤怠の場合、すべてのケースを翌日として扱う
                    if ($isOvernight) {
                        // 日をまたぐ場合：すべて正常として扱う（エラーなし）
                    } else {
                        // 修正で日跨ぎでない場合でも、元の勤怠が日跨ぎなら許可
                        // ただし、出勤時間 < 退勤時間 でなければエラー
                        if ($clockIn->greaterThanOrEqualTo($clockOut)) {
                            // 1つのエラーメッセージのみを表示するため、カスタムキーを使用
                            $validator->errors()->add('clock_time', '出勤時間もしくは退勤時間が不適切な値です');
                        }
                    }
                }
            }

            // 休憩時間のバリデーション
            foreach ($breakStartTimes as $index => $breakStartTime) {
                $breakEndTime = $breakEndTimes[$index] ?? null;

                // 開始時間と終了時間の両方が空（null、空文字列）の場合はスキップ（空白の休憩は許可）
                if (empty($breakStartTime) && empty($breakEndTime)) {
                    continue;
                }

                // 片方だけ入力されている場合はエラー
                if (empty($breakStartTime) || empty($breakEndTime)) {
                    $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                    $validator->errors()->add("break_end_times.{$index}", '休憩時間が不適切な値です');
                    continue;
                }

                // 開始時間と終了時間の両方が存在する場合のみバリデーションを実行
                if ($breakStartTime && $breakEndTime) {
                    $breakStart = Carbon::createFromFormat('H:i', $breakStartTime);
                    $breakEnd = Carbon::createFromFormat('H:i', $breakEndTime);

                    // 休憩時間が日跨ぎかどうかを判定（休憩終了時間が休憩開始時間より小さい場合）
                    $isBreakOvernight = $breakEnd->lessThan($breakStart) || $breakEnd->equalTo($breakStart);

                    // 休憩開始時間が休憩終了時間より後（日跨ぎでない場合のみエラー）
                    if (!$isBreakOvernight && $breakStart->greaterThanOrEqualTo($breakEnd)) {
                        $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                        $validator->errors()->add("break_end_times.{$index}", '休憩時間が不適切な値です');
                    }

                    // 休憩開始時間が出勤時間より前、または退勤時間より後
                    if ($clockInTime && $clockOutTime) {
                        $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                        $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                        
                        // 日跨ぎ勤怠の場合の処理
                        if ($isOvernight) {
                            // 日跨ぎ勤怠の場合、休憩開始時間が退勤時間より前なら翌日として扱う
                            $breakStartForComparison = $breakStart->lessThan($clockOut) 
                                ? $breakStart->copy()->addDay() 
                                : $breakStart;
                            
                            // 休憩開始時間が出勤時間より前はエラー（翌日として扱った場合でも）
                            if (!$breakStart->lessThan($clockOut) && $breakStart->lessThan($clockIn)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                            
                            // 休憩開始時間が退勤時間より後はエラー（翌日として扱った場合）
                            $clockOutForComparison = $clockOut->copy()->addDay();
                            if ($breakStartForComparison->greaterThan($clockOutForComparison)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                        } else {
                            // 通常の勤怠の場合
                            // 休憩開始時間が出勤時間より前はエラー
                            if ($breakStart->lessThan($clockIn)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                            
                            // 休憩開始時間が退勤時間より後はエラー
                            if ($breakStart->greaterThan($clockOut)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                        }
                    } else {
                        // 出勤時間または退勤時間が未入力の場合の簡易チェック
                        if ($clockInTime) {
                            $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                            if ($breakStart->lessThan($clockIn)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                        }
                        
                        if ($clockOutTime) {
                            $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                            $clockOutForComparison = $isOvernight ? $clockOut->copy()->addDay() : $clockOut;
                            $breakStartForComparison = $isOvernight && $breakStart->lessThan($clockOut) 
                                ? $breakStart->copy()->addDay() 
                                : $breakStart;
                            
                            if ($breakStartForComparison->greaterThan($clockOutForComparison)) {
                                $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                            }
                        }
                    }

                    // 休憩終了時間が退勤時間より後
                    if ($clockOutTime) {
                        $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                        
                        // 休憩時間が日跨ぎの場合、休憩終了時間を翌日として扱う
                        $breakEndForComparison = $isBreakOvernight ? $breakEnd->copy()->addDay() : $breakEnd;
                        
                        // 日をまたぐ勤怠の場合、退勤時間を翌日として扱う
                        $clockOutForComparison = $isOvernight ? $clockOut->copy()->addDay() : $clockOut;
                        
                        // 休憩終了時間が退勤時間より後はエラー
                        // 日跨ぎの休憩時間や日跨ぎ勤怠の場合、適切に日付を考慮して比較
                        if ($breakEndForComparison->greaterThan($clockOutForComparison)) {
                            $validator->errors()->add("break_end_times.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
                        }
                    }
                }
            }
        });
    }

    /**
     * バリデーションメッセージ（共通）
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
            'note.max' => '備考は500文字以内で入力してください',
            'pending_request' => '*承認待ちのため修正はできません。',
        ];
    }
}

