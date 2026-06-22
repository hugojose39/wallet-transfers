<?php

declare(strict_types=1);

namespace App\Domain\User\Entities;

use App\Domain\User\Enums\UserType;
use App\Domain\Shared\Exceptions\UnauthorizedTransferException;

final class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $cpfCnpj,
        private readonly string $email,
        private readonly UserType $type,
        private readonly Wallet $wallet,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCpfCnpj(): string
    {
        return $this->cpfCnpj;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function assertCanTransfer(): void
    {
        if (! $this->type->canSend()) {
            throw new UnauthorizedTransferException(
                sprintf('User type "%s" is not allowed to send transfers.', $this->type->value)
            );
        }
    }

    public function isMerchant(): bool
    {
        return $this->type === UserType::MERCHANT;
    }
}
