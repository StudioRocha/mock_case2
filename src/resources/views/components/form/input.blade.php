{{-- 入力フィールドコンポーネント --}}
<div class="form-group">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
    </label>
    <input
        type="{{ $type ?? 'text' }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ $value ?? old($name) }}"
        class="form-input @error($name) is-invalid @enderror"
        @if(isset($required)
        &&
        $required)
        required
        @endif
        @if(isset($placeholder))
        placeholder="{{ $placeholder }}"
        @endif
    />
    {{-- バリデーションエラーメッセージ --}}
    @error($name)
    <div class="form-error">{{ $message }}</div>
    @enderror
</div>
