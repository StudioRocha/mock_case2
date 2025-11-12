{{-- ボタンコンポーネント（リンクまたはボタン） --}}
@if(isset($url))
<a href="{{ $url }}" class="btn btn--{{ $type ?? 'primary' }}">{{ $text }}</a>
@else
<button
    type="{{ $buttonType ?? 'submit' }}"
    class="btn btn--{{ $type ?? 'primary' }}"
>
    {{ $text }}
</button>
@endif
