<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $balance
 * @property int $version
 */
final class WalletModel extends Model
{
    protected ?string $table = 'wallets';

    protected array $fillable = ['user_id', 'balance'];

    protected array $casts = [
        'balance' => 'integer',
    ];

    public function user(): \Hyperf\Database\Model\Relations\BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
