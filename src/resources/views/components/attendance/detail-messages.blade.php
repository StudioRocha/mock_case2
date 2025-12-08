{{-- 成功メッセージの表示 --}}
@if(session('success'))
<div class="attendance-detail-message attendance-detail-message--success">
    {{ session('success') }}
</div>
@endif

{{-- エラーメッセージの表示 --}}
@if(session('error'))
<div class="attendance-detail-message attendance-detail-message--error">
    {{ session('error') }}
</div>
@endif

{{-- バリデーションエラーメッセージの表示 --}}
@if($errors->any())
<div class="attendance-detail-message attendance-detail-message--error">
    <ul>
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

