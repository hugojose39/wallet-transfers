<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Resources;

use App\Domain\User\Entities\User;

final class UserResource
{
    public function __construct(private readonly User $user) {}

    public function toArray(): array
    {
        return [
            'id' => $this->user->getId(),
            'name' => $this->user->getName(),
            'document' => $this->user->getCpfCnpj(),
            'email' => $this->user->getEmail(),
            'type' => $this->user->getType()->value,
        ];
    }
}
