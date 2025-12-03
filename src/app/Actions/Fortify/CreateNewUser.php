<?php

namespace App\Actions\Fortify;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        // RegisterRequestのバリデーションルールとメッセージを使用
        $request = new RegisterRequest();
        $rules = $request->rules();
        $messages = $request->messages();

        Validator::make($input, $rules, $messages)->validate();

        // 一般ユーザーのロールを取得
        $userRole = Role::where('name', Role::NAME_USER)->firstOrFail();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role_id' => $userRole->id,
        ]);
    }
}

