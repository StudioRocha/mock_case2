{{-- ユーザー名（読み取り専用） --}}
<div class="attendance-detail-item">
    <span class="attendance-detail-label">名前</span>
    <span class="attendance-detail-value attendance-detail-name">{{ $userName }}</span>
</div>

{{-- 勤怠日付（読み取り専用） --}}
<div class="attendance-detail-item">
    <span class="attendance-detail-label">日付</span>
    <span class="attendance-detail-value attendance-detail-date-year">{{ $dateYear }}</span>
    <span class="attendance-detail-date-month-day">{{ $dateMonthDay }}</span>
</div>

{{-- 出勤・退勤時間 --}}
<div class="attendance-detail-item">
    <span class="attendance-detail-label">出勤・退勤</span>
    <span class="attendance-detail-time-col">
        <input
            type="time"
            @if(isset($clockInTimeName)) name="{{ $clockInTimeName }}" @endif
            value="{{ $displayClockInTime }}"
            class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
            @if(!$canEdit) disabled @endif
            @if(isset($isReadonly) && $isReadonly) readonly @endif
        />
    </span>
    <span class="attendance-detail-time-separator">~</span>
    <span class="attendance-detail-time-col">
        <input
            type="time"
            @if(isset($clockOutTimeName)) name="{{ $clockOutTimeName }}" @endif
            value="{{ $displayClockOutTime }}"
            class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
            @if(!$canEdit) disabled @endif
            @if(isset($isReadonly) && $isReadonly) readonly @endif
        />
    </span>
</div>

{{-- 休憩時間の一覧表示 --}}
@foreach($breakDetails as $index => $break)
    @php
        $startTime = $break['start_time'] ?? '';
        $endTime = $break['end_time'] ?? '';
        
        // break_numberが既に計算されている場合（管理者用の修正申請詳細画面など）
        if (isset($break['break_number'])) {
            $breakNumber = $break['break_number'];
            $shouldDisplay = $break['should_display'] ?? true;
        } else {
            // 有効な休憩かどうか（開始時間と終了時間の両方が存在する場合）
            $hasValidBreak = !empty($startTime) && !empty($endTime) && $startTime !== '-' && $endTime !== '-';
            // 最後の要素（修正用の空白休憩）かどうかを判定
            $isLastBreak = $index === count($breakDetails) - 1;
            // 有効な休憩、または最後の空白休憩の場合は表示
            $shouldDisplay = $hasValidBreak || $isLastBreak;
            // 表示する休憩の番号を決定
            $breakNumber = $hasValidBreak ? ($index + 1) : (($validBreakCount ?? 0) + 1);
        }
    @endphp
    @if($shouldDisplay)
    <div class="attendance-detail-item">
        <span class="attendance-detail-label">
            @if($breakNumber === 1)
                休憩
            @else
                休憩{{ $breakNumber }}
            @endif
        </span>
        <span class="attendance-detail-time-col">
            <input
                type="time"
                @if(isset($breakStartTimeName)) name="{{ $breakStartTimeName }}[]" @endif
                value="{{ $startTime }}"
                class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
                @if(!$canEdit) disabled @endif
                @if(isset($isReadonly) && $isReadonly) readonly @endif
            />
        </span>
        <span class="attendance-detail-time-separator">~</span>
        <span class="attendance-detail-time-col">
            <input
                type="time"
                @if(isset($breakEndTimeName)) name="{{ $breakEndTimeName }}[]" @endif
                value="{{ $endTime }}"
                class="attendance-detail-time-input-field {{ !$canEdit ? 'attendance-detail-time-input-field--readonly' : '' }}"
                @if(!$canEdit) disabled @endif
                @if(isset($isReadonly) && $isReadonly) readonly @endif
            />
        </span>
    </div>
    @endif
@endforeach

{{-- 備考欄 --}}
<div class="attendance-detail-item">
    <span class="attendance-detail-label">備考</span>
    <textarea
        @if(isset($noteName)) name="{{ $noteName }}" @endif
        class="attendance-detail-note-field {{ !$canEdit ? 'attendance-detail-note-field--readonly' : '' }}"
        @if(!$canEdit) disabled @endif
        @if(isset($isReadonly) && $isReadonly) readonly @endif
    >{{ $displayNote ?? '' }}</textarea>
</div>

