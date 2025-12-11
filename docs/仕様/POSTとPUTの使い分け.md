# POSTとPUTの使い分け

## 概要

このドキュメントでは、HTTPメソッドの`POST`と`PUT`の使い分けを、実際のアクション（`approve()`と`update()`）を例に説明します。

---

## 比較表

| 項目 | POST（承認処理） | PUT（更新処理） |
|---|---|---|
| **アクション** | `approve()` | `update()` |
| **コントローラー** | `Admin\StampCorrectionRequestController` | `Admin\AttendanceController` |
| **ルート** | `/stamp_correction_request/approve/{id}` | `/admin/attendance/{id}` |
| **対象リソース** | 複数（`attendances`, `break_times`, `stamp_correction_requests`） | 単一（`attendance` + 関連`break_times`） |
| **操作の種類** | 処理の実行（承認アクション） | リソースの置き換え |
| **冪等性** | なし（2回目はエラー） | あり（何度実行しても同じ結果） |
| **URLの意味** | 「{id}を承認する」 | 「{id}のリソースを置き換える」 |

---

## POSTを使うケース：`approve()`（承認処理）

### 実装例

```php
// Admin\StampCorrectionRequestController@approve
public function approve($attendance_correct_request_id)
{
    // 1. 承認待ちチェック（2回目はエラー）
    if ($stampCorrectionRequest->status !== STATUS_PENDING) {
        return redirect()->with('error', 'この申請は既に処理済みです。');
    }

    DB::beginTransaction();
    try {
        // 2. 複数のリソースに影響
        // - attendancesテーブルを更新
        $attendance->clock_in_time = $stampCorrectionRequest->requested_clock_in_time;
        $attendance->clock_out_time = $stampCorrectionRequest->requested_clock_out_time;
        $attendance->note = $stampCorrectionRequest->requested_note;
        $attendance->save();

        // - break_timesテーブルを削除して再作成
        $attendance->breaks()->delete();
        foreach ($stampCorrectionRequest->breakCorrections as $breakCorrection) {
            BreakTime::create([...]);
        }

        // - stamp_correction_requestsテーブルを更新
        $stampCorrectionRequest->update([
            'status' => STATUS_APPROVED,
            'approved_at' => Carbon::now(),
        ]);

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
    }
}
```

### POSTを使う理由

1. **複数リソースに影響する処理**
   - `attendances`、`break_times`、`stamp_correction_requests`の3つのテーブルに影響
   - PUTは単一リソースの置き換えに適している

2. **処理の実行（アクション）**
   - 「承認」という処理を実行する
   - リソースの置き換えではなく、ビジネスロジックの実行

3. **冪等性がない**
   - 同じ申請を2回承認しようとするとエラーになる
   - POSTは冪等性を保証しない

4. **URLが処理を表す**
   - `/approve/{id}` = 「{id}を承認する」という意味
   - リソースの識別ではなく、処理の識別

---

## PUTを使うケース：`update()`（更新処理）

### 実装例

```php
// Admin\AttendanceController@update
public function update($id, AdminAttendanceUpdateRequest $request)
{
    $attendance = Attendance::findOrFail($id);

    DB::beginTransaction();
    try {
        // 1. 単一リソース（attendance）を置き換え
        $attendance->clock_in_time = $request->clock_in_time;
        $attendance->clock_out_time = $request->clock_out_time;
        $attendance->note = $request->note;
        $attendance->save();

        // 2. 関連リソース（break_times）も置き換え
        $attendance->breaks()->delete(); // 既存を削除
        foreach ($request->break_start_times as $index => $breakStartTime) {
            BreakTime::create([...]); // 新しいデータで再作成
        }

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
    }
}
```

### PUTを使う理由

1. **単一リソースの置き換え**
   - 主な対象は`attendance`リソース（`break_times`は関連データとして扱う）
   - リソース全体を新しいデータで置き換える操作

2. **冪等性がある**
   - 同じリクエストを何度実行しても結果が同じ
   - 既存データを削除してから再作成するため、常に同じ状態になる

3. **URLがリソースを表す**
   - `/admin/attendance/{id}` = 「{id}の勤怠リソース」を表す
   - リソースの識別が明確

4. **RESTful設計に適合**
   - リソースの状態を完全に置き換える操作として定義されている

---

## 判断基準

### POSTを使うべき場合

- ✅ 複数のリソースに影響する処理
- ✅ 処理の実行（アクション）を表す
- ✅ 冪等性がない（同じ処理を2回実行できない）
- ✅ URLが処理を表す（例：`/approve/{id}`）

### PUTを使うべき場合

- ✅ 単一リソースの置き換え
- ✅ リソースの状態を完全に置き換える
- ✅ 冪等性がある（何度実行しても同じ結果）
- ✅ URLがリソースを表す（例：`/attendance/{id}`）

---

## まとめ

| 観点 | POST | PUT |
|---|---|---|
| **主な用途** | 処理の実行 | リソースの置き換え |
| **対象リソース** | 複数でも可 | 単一が基本 |
| **冪等性** | 不要 | 必要 |
| **URLの意味** | 処理を表す | リソースを表す |

**このアプリでの例：**
- **POST**: `approve()` - 修正申請を承認する（複数リソースに影響、冪等性なし）
- **PUT**: `update()` - 勤怠情報を更新する（単一リソースの置き換え、冪等性あり）

