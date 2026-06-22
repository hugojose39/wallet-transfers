<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum UserType: string
{
    case COMMON = 'common';
    case MERCHANT = 'merchant';

    public function canSend(): bool
    {
        return $this === self::COMMON;
    }

    public function canReceive(): bool
    {
        return true;
    }
}
