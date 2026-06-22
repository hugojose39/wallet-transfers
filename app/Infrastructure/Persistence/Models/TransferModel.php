<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $payer_id
 * @property int $payee_id
 * @property int $amount
 * @property string $status
 */
final class TransferModel extends Model
{
    protected ?string $table = 'transfers';

    protected array $fillable = ['payer_id', 'payee_id', 'amount', 'status'];

    protected array $casts = [
        'amount' => 'integer',
    ];

    public function payer(): \Hyperf\Database\Model\Relations\BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'payer_id');
    }

    public function payee(): \Hyperf\Database\Model\Relations\BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'payee_id');
    }
}
