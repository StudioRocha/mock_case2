<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeは:dateより後の日付にしてください。',
    'after_or_equal' => ':attributeは:date以降の日付にしてください。',
    'alpha' => ':attributeは英字のみにしてください。',
    'alpha_dash' => ':attributeは英数字、ハイフン、アンダースコアのみにしてください。',
    'alpha_num' => ':attributeは英数字のみにしてください。',
    'array' => ':attributeは配列にしてください。',
    'before' => ':attributeは:dateより前の日付にしてください。',
    'before_or_equal' => ':attributeは:date以前の日付にしてください。',
    'between' => [
        'numeric' => ':attributeは:min〜:maxの間で指定してください。',
        'file' => ':attributeは:min〜:max KBの間で指定してください。',
        'string' => ':attributeは:min〜:max文字の間で指定してください。',
        'array' => ':attributeは:min〜:max個の間で指定してください。',
    ],
    'boolean' => ':attributeは真偽値にしてください。',
    'confirmed' => ':attributeの確認が一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付ではありません。',
    'date_equals' => ':attributeは:dateと同じ日付にしてください。',
    'date_format' => ':attributeは:format形式で入力してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherは異なる値にしてください。',
    'digits' => ':attributeは:digits桁で入力してください。',
    'digits_between' => ':attributeは:min〜:max桁で入力してください。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeは重複しています。',
    'email' => ':attributeは有効なメールアドレス形式で入力してください。',
    'ends_with' => ':attributeは次のいずれかで終わる必要があります: :values',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'file' => ':attributeはファイルにしてください。',
    'filled' => ':attributeは必須です。',
    'gt' => [
        'numeric' => ':attributeは:valueより大きい値にしてください。',
        'file' => ':attributeは:value KBより大きいファイルにしてください。',
        'string' => ':attributeは:value文字より多い文字数にしてください。',
        'array' => ':attributeは:value個より多い要素にしてください。',
    ],
    'gte' => [
        'numeric' => ':attributeは:value以上の値にしてください。',
        'file' => ':attributeは:value KB以上のファイルにしてください。',
        'string' => ':attributeは:value文字以上の文字数にしてください。',
        'array' => ':attributeは:value個以上の要素にしてください。',
    ],
    'image' => ':attributeは画像にしてください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'integer' => ':attributeは整数にしてください。',
    'ip' => ':attributeは有効なIPアドレスにしてください。',
    'ipv4' => ':attributeは有効なIPv4アドレスにしてください。',
    'ipv6' => ':attributeは有効なIPv6アドレスにしてください。',
    'json' => ':attributeは有効なJSON文字列にしてください。',
    'lt' => [
        'numeric' => ':attributeは:valueより小さい値にしてください。',
        'file' => ':attributeは:value KBより小さいファイルにしてください。',
        'string' => ':attributeは:value文字より少ない文字数にしてください。',
        'array' => ':attributeは:value個より少ない要素にしてください。',
    ],
    'lte' => [
        'numeric' => ':attributeは:value以下の値にしてください。',
        'file' => ':attributeは:value KB以下のファイルにしてください。',
        'string' => ':attributeは:value文字以下の文字数にしてください。',
        'array' => ':attributeは:value個以下の要素にしてください。',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下の値にしてください。',
        'file' => ':attributeは:max KB以下のファイルにしてください。',
        'string' => ':attributeは:max文字以下にしてください。',
        'array' => ':attributeは:max個以下の要素にしてください。',
    ],
    'mimes' => ':attributeは:valuesタイプのファイルにしてください。',
    'mimetypes' => ':attributeは:valuesタイプのファイルにしてください。',
    'min' => [
        'numeric' => ':attributeは:min以上の値にしてください。',
        'file' => ':attributeは:min KB以上のファイルにしてください。',
        'string' => ':attributeは:min文字以上にしてください。',
        'array' => ':attributeは:min個以上の要素にしてください。',
    ],
    'multiple_of' => ':attributeは:valueの倍数にしてください。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeは数値にしてください。',
    'password' => 'パスワードが正しくありません。',
    'present' => ':attributeは存在している必要があります。',
    'prohibited' => ':attributeは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは禁止されています。',
    'prohibited_unless' => ':otherが:valuesでない限り、:attributeは禁止されています。',
    'prohibits' => ':attributeは:otherを禁止します。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeを入力してください。',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesでない限り、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesが存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesが存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致する必要があります。',
    'size' => [
        'numeric' => ':attributeは:sizeにしてください。',
        'file' => ':attributeは:size KBにしてください。',
        'string' => ':attributeは:size文字にしてください。',
        'array' => ':attributeは:size個の要素にしてください。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります: :values',
    'string' => ':attributeは文字列にしてください。',
    'timezone' => ':attributeは有効なタイムゾーンにしてください。',
    'unique' => ':attributeは既に使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'url' => ':attributeは有効なURLにしてください。',
    'uuid' => ':attributeは有効なUUIDにしてください。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "rule.attribute" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
    ],

];



