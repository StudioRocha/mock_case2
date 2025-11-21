<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * リクエストの認証を許可するかどうか
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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

            // 出勤時間と退勤時間のバリデーション
            if ($clockInTime && $clockOutTime) {
                $clockIn = Carbon::createFromFormat('H:i', $clockInTime);
                $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);

                if ($clockIn->greaterThanOrEqualTo($clockOut)) {
                    $validator->errors()->add('clock_in_time', '出勤時間もしくは退勤時間が不適切な値です');
                    $validator->errors()->add('clock_out_time', '出勤時間もしくは退勤時間が不適切な値です');
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
                        if ($breakStart->greaterThan($clockOut)) {
                            $validator->errors()->add("break_start_times.{$index}", '休憩時間が不適切な値です');
                        }
                    }

                    // 休憩終了時間が退勤時間より後
                    if ($clockOutTime) {
                        $clockOut = Carbon::createFromFormat('H:i', $clockOutTime);
                        if ($breakEnd->greaterThan($clockOut)) {
                            $validator->errors()->add("break_end_times.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
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
        ];
    }
}

