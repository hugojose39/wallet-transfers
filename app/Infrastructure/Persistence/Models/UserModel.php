<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $document
 * @property string $email
 * @property string $password
 * @property string $type
 */
final class UserModel extends Model
{
    protected ?string $table = 'users';

    protected array $fillable = ['name', 'document', 'email', 'password', 'type'];

    protected array $hidden = ['password'];

    public function wallet(): \Hyperf\Database\Model\Relations\HasOne
    {
        return $this->hasOne(WalletModel::class, 'user_id');
    }

    public function transfers(): \Hyperf\Database\Model\Relations\HasMany
    {
        return $this->hasMany(TransferModel::class, 'payer_id');
    }
}
