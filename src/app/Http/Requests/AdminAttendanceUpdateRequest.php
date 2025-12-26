<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Requests\Concerns\ValidatesAttendanceData;

class AdminAttendanceUpdateRequest extends FormRequest
{
    // トレイト参照
    use ValidatesAttendanceData;

    /**
     * リクエストの認証を許可するかどうか
     * 管理者のみ許可
     *
     * @return bool
     */
    public function authorize()
    {
        // 管理者のみ許可
        /** @var User|null $user */
        $user = Auth::user();
        return $user instanceof User && $user->isAdmin();
    }
}

