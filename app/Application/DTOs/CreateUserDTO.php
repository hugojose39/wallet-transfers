<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\User\Enums\UserType;

final class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $document,
        public readonly string $email,
        public readonly string $password,
        public readonly UserType $type,
    ) {
    }
}
