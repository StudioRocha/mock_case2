# 実装パターン

## 概要

勤怠管理アプリにおける、一般ユーザー向け画面と管理者向け画面の効率的な実装パターンをまとめたドキュメントです。

パスが異なる画面や、同じパスでミドルウェアで区別する画面など、仕様に応じた最適な実装方法を定義します。

---

## パターン分析

### パターン1: パスが異なる + 見た目がほぼ同じ

以下の画面は、パスが異なりますが、見た目や機能がほぼ同じです：

- **PG02** (`/login`) vs **PG07** (`/admin/login`) - ログイン画面
- **PG04** (`/attendance/list`) vs **PG08** (`/admin/attendance/list`) - 勤怠一覧画面
- **PG05** (`/attendance/detail/{id}`) vs **PG09** (`/admin/attendance/{id}`) - 勤怠詳細画面

### パターン2: 同じパス + ミドルウェアで区別

以下の画面は、同じパスを使用し、認証ミドルウェアでユーザーのロールを確認して画面表示を切り替えます：

- **PG06** と **PG12** - `/stamp_correction_request/list` - 申請一覧画面

---

## 推奨実装パターン

### パターンA: ログイン画面（PG02, PG07）

**仕様:**
- PG02: `/login` (一般ユーザー)
- PG07: `/admin/login` (管理者)
- パスが異なる
- 見た目がほぼ同じ

**実装方針:**
- **共通ビュー**を使用
- **コントローラーでパラメータを渡す**ことで違いを表現

**実装構造:**
```
resources/views/
└── auth/
    └── login.blade.php              # 共通ログイン画面
```

**コントローラー実装例:**
```php
// LoginController.php（一般ユーザー用）
public function showLoginForm()
{
    return view('auth.login', [
        'isAdmin' => false,
        'title' => 'ログイン',
        'formAction' => route('login'),
        'showRegisterLink' => true,
    ]);
}

// Admin/LoginController.php（管理者用）
public function showLoginForm()
{
    return view('auth.login', [  // 同じビューを使用
        'isAdmin' => true,
        'title' => '管理者ログイン',
        'formAction' => route('admin.login'),
        'showRegisterLink' => false,
    ]);
}
```

**ビュー実装例:**
```blade
{{-- auth/login.blade.php --}}
@extends('layouts.app')

@section('title', $title . ' - CT COACHTECH')

@section('content')
<div class="form-container">
    <h1 class="page-title">{{ $title }}</h1>

    <form method="POST" action="{{ $formAction }}" class="form">
        @csrf
        @include('components.form.input', [
            'name' => 'email',
            'label' => 'メールアドレス',
            'type' => 'email',
            'required' => true
        ])
        @include('components.form.input', [
            'name' => 'password',
            'label' => 'パスワード',
            'type' => 'password',
            'required' => true
        ])

        <div class="form__submit">
            @include('components.button', [
                'type' => 'primary',
                'text' => 'ログイン',
                'buttonType' => 'submit'
            ])
        </div>
    </form>

    @if($showRegisterLink)
        <a href="{{ route('register') }}" class="link link--login">会員登録はこちら</a>
    @endif
</div>
@endsection
```

---

### パターンB: 勤怠一覧画面（PG04, PG08）

**仕様:**
- PG04: `/attendance/list` (一般ユーザー)
- PG08: `/admin/attendance/list` (管理者)
- パスが異なる
- 構造が似ている（管理者は全スタッフの一覧を表示）

**実装方針:**
- **共通ビュー**を使用
- **コントローラーでデータ取得ロジックを分岐**
- **パラメータで表示内容を切り替え**

**実装構造:**
```
resources/views/
└── attendance/
    └── list.blade.php                # 共通勤怠一覧画面
```

**コントローラー実装例:**
```php
// AttendanceController.php（一般ユーザー用）
public function list()
{
    $attendances = auth()->user()->attendances()
        ->orderBy('date', 'desc')
        ->paginate(10);

    return view('attendance.list', [
        'isAdmin' => false,
        'attendances' => $attendances,
        'title' => '勤怠一覧',
    ]);
}

// Admin/AttendanceController.php（管理者用）
public function list()
{
    $attendances = Attendance::with('user')
        ->orderBy('date', 'desc')
        ->paginate(10);

    return view('attendance.list', [  // 同じビューを使用
        'isAdmin' => true,
        'attendances' => $attendances,
        'title' => '勤怠一覧（管理者）',
    ]);
}
```

**ビュー実装例:**
```blade
{{-- attendance/list.blade.php --}}
@extends('layouts.app')

@section('title', $title . ' - CT COACHTECH')

@section('content')
<div class="attendance-list-container">
    <h1 class="page-title">{{ $title }}</h1>

    @if($isAdmin)
        <p>全スタッフの勤怠情報を表示しています。</p>
    @endif

    <table class="attendance-table">
        <thead>
            <tr>
                @if($isAdmin)
                    <th>スタッフ名</th>
                @endif
                <th>日付</th>
                <th>出勤時刻</th>
                <th>退勤時刻</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
                <tr>
                    @if($isAdmin)
                        <td>{{ $attendance->user->name }}</td>
                    @endif
                    <td>{{ $attendance->date->format('Y/m/d') }}</td>
                    <td>{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '-' }}</td>
                    <td>
                        <a href="{{ $isAdmin ? route('admin.attendance.show', $attendance->id) : route('attendance.detail', $attendance->id) }}">
                            詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $attendances->links() }}
</div>
@endsection
```

---

### パターンC: 勤怠詳細画面（PG05, PG09）

**仕様:**
- PG05: `/attendance/detail/{id}` (一般ユーザー)
- PG09: `/admin/attendance/{id}` (管理者)
- パスが異なる
- 構造が似ている（管理者は編集可能）

**実装方針:**
- **共通ビュー**を使用
- **コントローラーで権限チェックとデータ取得**
- **パラメータで編集可否を切り替え**

**実装構造:**
```
resources/views/
└── attendance/
    └── detail.blade.php              # 共通勤怠詳細画面
```

**コントローラー実装例:**
```php
// AttendanceController.php（一般ユーザー用）
public function detail($id)
{
    $attendance = Attendance::findOrFail($id);
    
    // 自分の勤怠のみ閲覧可能
    if ($attendance->user_id !== auth()->id()) {
        abort(403);
    }

    return view('attendance.detail', [
        'isAdmin' => false,
        'attendance' => $attendance,
        'canEdit' => false,  // 一般ユーザーは編集不可（修正申請のみ）
        'title' => '勤怠詳細',
    ]);
}

// Admin/AttendanceController.php（管理者用）
public function show($id)
{
    $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

    return view('attendance.detail', [  // 同じビューを使用
        'isAdmin' => true,
        'attendance' => $attendance,
        'canEdit' => true,  // 管理者は編集可能
        'title' => '勤怠詳細（管理者）',
    ]);
}
```

**ビュー実装例:**
```blade
{{-- attendance/detail.blade.php --}}
@extends('layouts.app')

@section('title', $title . ' - CT COACHTECH')

@section('content')
<div class="attendance-detail-container">
    <h1 class="page-title">{{ $title }}</h1>

    @if($isAdmin)
        <p>スタッフ: {{ $attendance->user->name }}</p>
    @endif

    <div class="attendance-info">
        <p>日付: {{ $attendance->date->format('Y年m月d日') }}</p>
        <p>出勤時刻: {{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '-' }}</p>
        <p>退勤時刻: {{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '-' }}</p>
    </div>

    @if($canEdit)
        {{-- 管理者は直接編集可能 --}}
        <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}">
            @csrf
            @method('PUT')
            <!-- 編集フォーム -->
        </form>
    @else
        {{-- 一般ユーザーは修正申請のみ --}}
        <a href="{{ route('stamp_correction_request.create', $attendance->id) }}">
            修正申請する
        </a>
    @endif
</div>
@endsection
```

---

### パターンD: 申請一覧画面（PG06, PG12）

**仕様:**
- PG06: `/stamp_correction_request/list` (一般ユーザー)
- PG12: `/stamp_correction_request/list` (管理者)
- **同じパス**を使用
- 認証ミドルウェアでユーザーのロール（`role`）を確認して画面表示を切り替え

**実装方針:**
- **1つのコントローラー**で条件分岐
- **同じビュー**を使用（パラメータで切り替え）

**実装構造:**
```
resources/views/
└── stamp_correction_request/
    └── list.blade.php                # 共通申請一覧画面
```

**コントローラー実装例:**
```php
// StampCorrectionRequestController.php
public function list()
{
    $isAdmin = auth()->user()->role === 'admin';

    if ($isAdmin) {
        // 管理者: 全ユーザーの申請を表示
        $requests = StampCorrectionRequest::with(['user', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $title = '修正申請一覧（管理者）';
    } else {
        // 一般ユーザー: 自分の申請のみ表示
        $requests = auth()->user()->stampCorrectionRequests()
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $title = '申請一覧';
    }

    return view('stamp_correction_request.list', [
        'isAdmin' => $isAdmin,
        'requests' => $requests,
        'title' => $title,
    ]);
}
```

**ビュー実装例:**
```blade
{{-- stamp_correction_request/list.blade.php --}}
@extends('layouts.app')

@section('title', $title . ' - CT COACHTECH')

@section('content')
<div class="request-list-container">
    <h1 class="page-title">{{ $title }}</h1>

    @if($isAdmin)
        <p>全スタッフの修正申請を表示しています。</p>
    @endif

    <div class="request-tabs">
        <button class="tab-button active" data-tab="pending">承認待ち</button>
        <button class="tab-button" data-tab="approved">承認済み</button>
    </div>

    <div class="request-list">
        @foreach($requests as $request)
            <div class="request-item">
                @if($isAdmin)
                    <p>申請者: {{ $request->user->name }}</p>
                @endif
                <p>日付: {{ $request->attendance->date->format('Y/m/d') }}</p>
                <p>ステータス: {{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</p>
                <a href="{{ route('stamp_correction_request.show', $request->id) }}">詳細</a>
            </div>
        @endforeach
    </div>

    {{ $requests->links() }}
</div>
@endsection
```

---

## 実装のメリット

### 1. DRY原則の遵守
- コードの重複を避けられる
- 共通部分を1箇所で管理できる

### 2. 保守性の向上
- 修正が1箇所で済む
- バグの発生箇所が明確

### 3. 拡張性の確保
- 将来の差別化がしやすい
- パラメータを追加するだけで機能拡張可能

### 4. 一貫性の維持
- 見た目が統一される
- ユーザー体験が向上

### 5. 仕様書との整合性
- パス定義を遵守できる
- ミドルウェアでの区別も実現可能

---

## 実装チェックリスト

### パターンA（ログイン画面）
- [ ] 共通ビュー `auth/login.blade.php` を作成
- [ ] `LoginController` でパラメータを設定
- [ ] `Admin/LoginController` でパラメータを設定
- [ ] ビューでパラメータを使用して表示を切り替え
- [ ] ルーティングが正しく設定されているか確認

### パターンB（勤怠一覧画面）
- [ ] 共通ビュー `attendance/list.blade.php` を作成
- [ ] `AttendanceController@list` で一般ユーザー用データ取得
- [ ] `Admin/AttendanceController@list` で管理者用データ取得
- [ ] ビューで `$isAdmin` フラグを使用して表示を切り替え
- [ ] ページネーションが正しく動作するか確認

### パターンC（勤怠詳細画面）
- [ ] 共通ビュー `attendance/detail.blade.php` を作成
- [ ] `AttendanceController@detail` で権限チェック実装
- [ ] `Admin/AttendanceController@show` で管理者用データ取得
- [ ] ビューで `$canEdit` フラグを使用して編集可否を切り替え
- [ ] 一般ユーザーは修正申請のみ可能であることを確認

### パターンD（申請一覧画面）
- [ ] 共通ビュー `stamp_correction_request/list.blade.php` を作成
- [ ] `StampCorrectionRequestController@list` でロール判定実装
- [ ] 管理者と一般ユーザーでデータ取得ロジックを分岐
- [ ] ビューで `$isAdmin` フラグを使用して表示を切り替え
- [ ] 同じパスで正しく動作するか確認

---

## 注意事項

### 1. セキュリティ
- 権限チェックを必ず実装する
- 一般ユーザーが他のユーザーのデータにアクセスできないようにする
- 管理者のみが編集可能な機能は `$canEdit` フラグで制御する

### 2. パフォーマンス
- 必要に応じて `with()` や `load()` を使用してN+1問題を回避
- ページネーションを適切に実装する

### 3. エラーハンドリング
- `findOrFail()` を使用して存在しないリソースへのアクセスを防ぐ
- 権限がない場合は `abort(403)` を返す

### 4. テスト
- 一般ユーザーと管理者でそれぞれテストする
- 権限チェックが正しく動作することを確認する

---

## 関連ドキュメント

- [画面定義.md](./画面定義.md) - 画面一覧とパス定義
- [共通化.md](./共通化.md) - 共通コンポーネントの仕様
- [コード作成の注意点.md](./コード作成の注意点.md) - コード作成時の注意点

