<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\CreateUserDTO;
use App\Domain\Shared\Exceptions\DuplicateDocumentException;
use App\Domain\Shared\Exceptions\DuplicateEmailException;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Entities\User;

final class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(CreateUserDTO $dto): User
    {
        if ($this->userRepository->findByDocument($dto->document) !== null) {
            throw new DuplicateDocumentException('A user with this document already exists.');
        }

        if ($this->userRepository->findByEmail($dto->email) !== null) {
            throw new DuplicateEmailException('A user with this email already exists.');
        }

        $passwordHash = password_hash($dto->password, PASSWORD_BCRYPT);

        return $this->userRepository->create(
            $dto->name,
            $dto->document,
            $dto->email,
            $passwordHash,
            $dto->type,
        );
    }
}
