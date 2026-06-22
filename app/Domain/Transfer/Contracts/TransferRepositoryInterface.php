<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Contracts;

use App\Domain\Transfer\Entities\Transfer;

interface TransferRepositoryInterface
{
    public function save(Transfer $transfer): Transfer;

    public function findById(int $id): ?Transfer;

    /** @return Transfer[] */
    public function findByUserId(int $userId, int $page = 1, int $perPage = 15): array;
}
