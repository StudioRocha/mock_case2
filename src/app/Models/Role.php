<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * ロール名の定数
     */
    public const NAME_USER = 'user';
    public const NAME_ADMIN = 'admin';

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'label',
    ];

    /**
     * 管理者かどうかを判定
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->name === self::NAME_ADMIN;
    }

    /**
     * 一般ユーザーかどうかを判定
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->name === self::NAME_USER;
    }
}

