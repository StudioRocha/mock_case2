<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidatesLoginData;

class AdminLoginRequest extends FormRequest
{
    // トレイト参照
    use ValidatesLoginData;

    /**
     * リクエストの認証を許可するかどうか
     * ログイン前なので常にtrue
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}

