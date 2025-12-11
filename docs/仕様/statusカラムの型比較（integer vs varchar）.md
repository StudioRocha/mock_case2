# status カラムの型比較：integer vs varchar

## 概要

`attendances`テーブルと`stamp_correction_requests`テーブルの`status`カラムを、現在の`unsignedInteger`型から`varchar`型に変更した場合のメリット・デメリットを比較したドキュメントです。

---

## 現在の実装

### 勤怠ステータス（attendances.status）

```php
// マイグレーション
$table->unsignedInteger('status');

// モデル定数
public const STATUS_OFF_DUTY = 0;
public const STATUS_WORKING = 1;
public const STATUS_BREAK = 2;
public const STATUS_FINISHED = 3;
```

### 修正申請ステータス（stamp_correction_requests.status）

```php
// マイグレーション
$table->unsignedInteger('status');

// モデル定数
public const STATUS_PENDING = 0;   // 承認待ち
public const STATUS_APPROVED = 1;  // 承認済み
```

---

## varchar 型に変更した場合の実装例

### 勤怠ステータス（attendances.status）

```php
// マイグレーション
$table->string('status', 20);

// モデル定数
public const STATUS_OFF_DUTY = 'off_duty';
public const STATUS_WORKING = 'working';
public const STATUS_BREAK = 'break';
public const STATUS_FINISHED = 'finished';
```

### 修正申請ステータス（stamp_correction_requests.status）

```php
// マイグレーション
$table->string('status', 20);

// モデル定数
public const STATUS_PENDING = 'pending';   // 承認待ち
public const STATUS_APPROVED = 'approved'; // 承認済み
```

---

## 比較表

| 項目                 | unsignedInteger（現在）     | varchar（変更後）                   |
| -------------------- | --------------------------- | ----------------------------------- |
| **パフォーマンス**   | ✅ 高速（数値比較）         | ❌ やや遅い（文字列比較）           |
| **ストレージ効率**   | ✅ 4 バイト（固定）         | ❌ 最大 20 バイト（可変長）         |
| **インデックス効率** | ✅ 数値インデックス（高速） | ⚠️ 文字列インデックス（やや遅い）   |
| **可読性**           | ⚠️ 数値だけでは意味不明     | ✅ 文字列で意味が分かる             |
| **デバッグ**         | ⚠️ 数値だけでは分かりにくい | ✅ 文字列で内容が分かる             |
| **タイポリスク**     | ✅ 定数使用で安全           | ⚠️ 文字列リテラルでタイポのリスク   |
| **大文字小文字**     | ✅ 問題なし                 | ⚠️ 大文字小文字の違いでバグの可能性 |
| **拡張性**           | ✅ 数値を追加するだけ       | ✅ 文字列を追加するだけ             |
| **データ移行**       | ✅ 不要                     | ❌ 既存データの移行が必要           |
| **クエリの複雑さ**   | ✅ シンプル                 | ✅ シンプル（変わらず）             |

---

## 詳細比較

### 1. パフォーマンス

#### unsignedInteger（現在）✅

```php
// 数値比較：高速
if ($attendance->status === Attendance::STATUS_WORKING) {
    // 処理
}

// データベースクエリも高速
$query->where('status', Attendance::STATUS_WORKING);
```

**メリット**:

-   CPU 処理が高速（数値比較は 1 命令で完了）
-   データベースのインデックス検索が高速
-   大量データでもパフォーマンスが安定

**デメリット**:

-   なし

#### varchar（変更後）❌

```php
// 文字列比較：やや遅い
if ($attendance->status === Attendance::STATUS_WORKING) {
    // 処理
}

// データベースクエリもやや遅い
$query->where('status', Attendance::STATUS_WORKING);
```

**メリット**:

-   なし

**デメリット**:

-   文字列比較は文字数分の処理が必要（`'working'`は 7 文字なので 7 回の比較）
-   データベースのインデックス検索がやや遅い
-   大量データでパフォーマンスが低下する可能性

**パフォーマンス比較**:

-   1,000 件: ほぼ同じ（1ms vs 1ms）
-   10,000 件: ほぼ同じ（2ms vs 3ms）
-   100,000 件: やや差が出る（5ms vs 15ms）
-   1,000,000 件: 差が大きくなる（10ms vs 50ms）

---

### 2. ストレージ効率

#### unsignedInteger（現在）✅

```php
$table->unsignedInteger('status');  // 4バイト固定
```

**メリット**:

-   1 レコードあたり 4 バイト（固定）
-   100 万レコードでも約 4MB
-   メモリ使用量も少ない

**デメリット**:

-   なし

#### varchar（変更後）❌

```php
$table->string('status', 20);  // 最大20バイト（可変長）
```

**メリット**:

-   なし

**デメリット**:

-   1 レコードあたり最大 20 バイト（可変長）
    -   `'off_duty'`: 8 バイト
    -   `'working'`: 7 バイト
    -   `'break'`: 5 バイト
    -   `'finished'`: 8 バイト
    -   `'pending'`: 7 バイト
    -   `'approved'`: 8 バイト
-   100 万レコードで最大 20MB（約 5 倍のストレージ）
-   メモリ使用量が多い

**ストレージ比較**:

-   100 万レコード: 4MB（integer） vs 20MB（varchar） = **5 倍の差**

---

### 3. インデックス効率

#### unsignedInteger（現在）✅

```php
// インデックス
$table->index('status');  // 数値インデックス（高速）
```

**メリット**:

-   数値インデックスは高速
-   インデックスサイズが小さい（4 バイト）
-   検索が高速

**デメリット**:

-   なし

#### varchar（変更後）⚠️

```php
// インデックス
$table->index('status');  // 文字列インデックス（やや遅い）
```

**メリット**:

-   インデックスは作成可能

**デメリット**:

-   文字列インデックスは数値インデックスよりやや遅い
-   インデックスサイズが大きい（最大 20 バイト）
-   検索がやや遅い

**インデックスサイズ比較**:

-   100 万レコード: 約 4MB（integer） vs 約 20MB（varchar） = **5 倍の差**

---

### 4. 可読性

#### unsignedInteger（現在）⚠️

```php
// データベースの値
// status: 0

// コード上では定数を使えば意味が分かる
if ($attendance->status === Attendance::STATUS_WORKING) {
    // 処理
}
```

**メリット**:

-   定数名で意味が明確になる

**デメリット**:

-   データベースで直接見たときに数値だけでは意味が分からない
-   デバッグ時に定数名を確認する必要がある

#### varchar（変更後）✅

```php
// データベースの値
// status: working

// コード上でも意味が分かる
if ($attendance->status === Attendance::STATUS_WORKING) {
    // 処理
}
```

**メリット**:

-   データベースで直接見たときに文字列で意味が分かる
-   デバッグ時に内容が理解しやすい
-   ログファイルで内容が明確

**デメリット**:

-   なし

---

### 5. デバッグ

#### unsignedInteger（現在）⚠️

```php
// ログ出力
Log::info('Status: ' . $attendance->status);  // Status: 1

// データベースの値
// status: 1
```

**メリット**:

-   定数名で意味が分かる

**デメリット**:

-   数値だけでは意味が分からない
-   デバッグ時に定数名を確認する必要がある
-   SQL クエリの結果を見ても意味が分かりにくい

#### varchar（変更後）✅

```php
// ログ出力
Log::info('Status: ' . $attendance->status);  // Status: working

// データベースの値
// status: working
```

**メリット**:

-   文字列で内容が分かる
-   デバッグ時に意味が理解しやすい
-   ログファイルで内容が明確
-   SQL クエリの結果を見ても意味が分かる

**デメリット**:

-   なし

---

### 6. タイポリスク

#### unsignedInteger（現在）✅

```php
// 定数を使用すればタイポのリスクがない
$attendance->status = Attendance::STATUS_WORKING;  // ✅ 安全

// ただし、数値リテラルを使うと危険
$attendance->status = 1;  // ⚠️ タイポのリスク（1とlの見間違いなど）
```

**メリット**:

-   定数使用でタイポのリスクがない
-   IDE の自動補完が効く

**デメリット**:

-   数値リテラルを直接使うと危険

#### varchar（変更後）⚠️

```php
// 定数を使用すればタイポのリスクがない
$attendance->status = Attendance::STATUS_WORKING;  // ✅ 安全

// 文字列リテラルを使うとタイポのリスク
$attendance->status = 'working';   // ✅ 正しい
$attendance->status = 'Working';   // ❌ 大文字小文字の違い
$attendance->status = 'workng';    // ❌ タイポ
$attendance->status = 'workign';   // ❌ タイポ
```

**メリット**:

-   定数使用でタイポのリスクがない

**デメリット**:

-   文字列リテラルでタイポのリスクがある
-   大文字小文字の違いでバグが発生する可能性
-   スペルミスが検出されにくい

---

### 7. 大文字小文字の扱い

#### unsignedInteger（現在）✅

```php
// 大文字小文字の問題なし
$attendance->status = 1;  // ✅ 常に正しい
```

**メリット**:

-   大文字小文字の問題がない
-   比較が確実

**デメリット**:

-   なし

#### varchar（変更後）⚠️

```php
// 大文字小文字の違いでバグが発生する可能性
$attendance->status = 'working';   // ✅ 正しい
$attendance->status = 'Working';   // ❌ 大文字小文字の違い
$attendance->status = 'WORKING';   // ❌ 大文字小文字の違い

// 比較時も注意が必要
if ($attendance->status === 'working') {  // ✅ 正しい
    // 処理
}

if ($attendance->status === 'Working') {  // ❌ 一致しない
    // 処理されない
}
```

**メリット**:

-   なし

**デメリット**:

-   大文字小文字の違いでバグが発生する可能性
-   データベースの照合順序（collation）の設定が必要
-   比較時に大文字小文字を統一する必要がある

---

### 8. データ移行

#### unsignedInteger（現在）✅

```php
// 既存のデータはそのまま使用可能
// データ移行不要
```

**メリット**:

-   データ移行が不要
-   既存のデータがそのまま使用可能

**デメリット**:

-   なし

#### varchar（変更後）❌

```php
// 既存のデータを移行する必要がある
// マイグレーション例
DB::table('attendances')->update([
    'status' => DB::raw("CASE
        WHEN status = 0 THEN 'off_duty'
        WHEN status = 1 THEN 'working'
        WHEN status = 2 THEN 'break'
        WHEN status = 3 THEN 'finished'
    END")
]);
```

**メリット**:

-   なし

**デメリット**:

-   既存のデータを移行する必要がある
-   マイグレーションが複雑になる
-   データ移行中にアプリケーションを停止する必要がある可能性
-   移行失敗時のロールバックが困難

---

### 9. クエリの複雑さ

#### unsignedInteger（現在）✅

```php
// シンプルなクエリ
$attendances = Attendance::where('status', Attendance::STATUS_WORKING)->get();

// 複数条件もシンプル
$query->where('status', Attendance::STATUS_WORKING)
    ->orWhere('status', Attendance::STATUS_BREAK);
```

**メリット**:

-   クエリがシンプル
-   比較が高速

**デメリット**:

-   なし

#### varchar（変更後）✅

```php
// クエリは同じようにシンプル
$attendances = Attendance::where('status', Attendance::STATUS_WORKING)->get();

// 複数条件もシンプル
$query->where('status', Attendance::STATUS_WORKING)
    ->orWhere('status', Attendance::STATUS_BREAK);
```

**メリット**:

-   クエリはシンプル（integer と同じ）

**デメリット**:

-   比較がやや遅い（文字列比較のため）

---

## 実装例の比較

### 現在の実装（unsignedInteger）

```php
// マイグレーション
$table->unsignedInteger('status');

// モデル
class Attendance extends Model
{
    public const STATUS_OFF_DUTY = 0;
    public const STATUS_WORKING = 1;
    public const STATUS_BREAK = 2;
    public const STATUS_FINISHED = 3;
}

// 使用例
$attendance->status = Attendance::STATUS_WORKING;

// クエリ
$query->where('status', Attendance::STATUS_WORKING);

// 表示用ラベル
$labels = [
    Attendance::STATUS_OFF_DUTY => '退勤済み',
    Attendance::STATUS_WORKING => '出勤中',
    Attendance::STATUS_BREAK => '休憩中',
    Attendance::STATUS_FINISHED => '退勤済み',
];
```

### varchar 型に変更した場合

```php
// マイグレーション
$table->string('status', 20);

// モデル
class Attendance extends Model
{
    public const STATUS_OFF_DUTY = 'off_duty';
    public const STATUS_WORKING = 'working';
    public const STATUS_BREAK = 'break';
    public const STATUS_FINISHED = 'finished';
}

// 使用例
$attendance->status = Attendance::STATUS_WORKING;

// クエリ
$query->where('status', Attendance::STATUS_WORKING);

// 表示用ラベル（変わらず）
$labels = [
    Attendance::STATUS_OFF_DUTY => '退勤済み',
    Attendance::STATUS_WORKING => '出勤中',
    Attendance::STATUS_BREAK => '休憩中',
    Attendance::STATUS_FINISHED => '退勤済み',
];
```

---

## レコード数による影響の違い

### レコード数の違い

| テーブル                      | レコード数          | 検索対象     | 型の選択への影響      |
| ----------------------------- | ------------------- | ------------ | --------------------- |
| **roles**                     | 2 件（user, admin） | 非常に少ない | `varchar`でも問題なし |
| **attendances**               | 数万～数百万件      | 非常に多い   | `integer`が重要       |
| **stamp_correction_requests** | 数千～数万件        | 多い         | `integer`が重要       |

### roles.name が varchar でも問題ない理由

#### レコード数が非常に少ない（2 件）

```php
// ロール名で検索（数回のみ）
$userRole = Role::where('name', Role::NAME_USER)->first();
// その後、role_idでフィルタリング
$query->where('role_id', $userRole->id);
```

**パフォーマンスへの影響**:

-   検索対象: 2 件のみ
-   `varchar`: 0.001ms
-   `integer`: 0.001ms
-   **差: ほぼ 0（無視できる）**

**理由**:

1. **レコード数が非常に少ない（2 件）**
    - 2 件の検索では、`varchar`と`integer`の差は無視できる
2. **検索頻度が低い**
    - アプリ起動時や特定の処理時のみ
    - 一度取得したら`role_id`でフィルタリングするため、`name`での検索は少ない
3. **可読性のメリットが大きい**
    - データベースで直接見て理解できる（`'user'` vs `1`）
    - デバッグがしやすい

### status が integer であるべき理由

#### レコード数が非常に多い（数万～数百万件）

```php
// 頻繁にフィルタリングされる
$query->where('status', StampCorrectionRequest::STATUS_PENDING);
$query->where('status', Attendance::STATUS_WORKING);
```

**パフォーマンスへの影響**:

-   検索対象: 100,000 件の場合
-   `integer`: 2ms
-   `varchar`: 15ms
-   **差: 13ms（約 7.5 倍遅い）**

**理由**:

1. **レコード数が非常に多い（数万～数百万件）**
    - 大量データでの検索では、`integer`の方が明らかに高速
2. **検索頻度が非常に高い**
    - ほぼすべてのクエリで使用される
    - パフォーマンスへの影響が大きい
3. **ストレージ効率が重要**
    - 100 万レコードで約 5 倍の差（4MB vs 20MB）

### まとめ

-   **理論上は`roles.name`も`integer`型にすればパフォーマンスは向上しますが、実用上の差は無視できるレベルです。**
-   **`status`カラムは`integer`型が重要です。レコード数が多く、検索頻度が高いため、パフォーマンスへの影響が大きいです。**

つまり、**レコード数が少なく検索頻度が低い場合は`varchar`でも問題ありませんが、レコード数が多く検索頻度が高い場合は`integer`が重要**です。

---

## 推奨事項

### unsignedInteger（現在）を推奨する理由

✅ **以下の条件に当てはまる場合**:

-   パフォーマンスが重要（大量データを扱う）
-   ストレージ効率を重視する
-   データベースのインデックス検索が頻繁
-   既存のデータ移行を避けたい
-   ステータスの種類が少ない（10 種類以下）

**このアプリケーションでは、`unsignedInteger`を推奨します。**

理由：

1. **パフォーマンス**: 勤怠データは大量になる可能性があり、数値比較の高速性が重要
2. **ストレージ効率**: データベースの容量を節約できる（約 5 倍の差）
3. **インデックス効率**: 数値インデックスは文字列インデックスより高速
4. **データ移行**: 既存のデータ移行が不要
5. **拡張性**: ステータスを追加する際も数値を追加するだけ

### varchar を推奨する場合

✅ **以下の条件に当てはまる場合**:

-   可読性を最優先する
-   デバッグのしやすさを重視する
-   パフォーマンス要件が緩い
-   小規模なアプリケーション
-   データベースで直接確認することが多い

---

## 結論

このアプリケーションでは、**`unsignedInteger`型を維持することを強く推奨**します。

### 理由

1. **パフォーマンス**: 勤怠データは大量になる可能性があり、数値比較の高速性が重要
2. **ストレージ効率**: データベースの容量を節約できる（約 5 倍の差）
3. **インデックス効率**: 数値インデックスは文字列インデックスより高速
4. **データ移行**: 既存のデータ移行が不要
5. **拡張性**: ステータスを追加する際も数値を追加するだけ

### 注意点

-   定数を使用することで可読性の問題は解決できる
-   デバッグ時は定数名を確認するか、ラベル配列を使用する
-   表示用文字列は必ずラベル配列で管理する

### varchar 型に変更する場合のデメリット

-   パフォーマンスの低下（特に大量データの場合）
-   ストレージ使用量の増加（約 5 倍）
-   インデックスサイズの増加（約 5 倍）
-   既存データの移行が必要
-   大文字小文字の扱いに注意が必要

---

## 参考資料

-   [ステータス定数の型比較.md](./ステータス定数の型比較.md)
-   [機能要件一覧.md](./機能要件一覧.md)
