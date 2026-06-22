<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Domain\User\Entities\User;
use App\Domain\User\Enums\UserType;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByIdOrFail(int $id): User;

    public function findByDocument(string $document): ?User;

    public function findByEmail(string $email): ?User;

    public function create(string $name, string $document, string $email, string $passwordHash, UserType $type): User;
}
