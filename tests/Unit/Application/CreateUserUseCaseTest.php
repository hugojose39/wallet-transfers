<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application;

use App\Application\DTOs\CreateUserDTO;
use App\Application\UseCases\CreateUserUseCase;
use App\Domain\Shared\Exceptions\DuplicateDocumentException;
use App\Domain\Shared\Exceptions\DuplicateEmailException;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Entities\User;
use App\Domain\User\Entities\Wallet;
use App\Domain\User\Enums\UserType;
use PHPUnit\Framework\TestCase;

final class CreateUserUseCaseTest extends TestCase
{
    private function makeUser(): User
    {
        $wallet = new Wallet(id: 1, userId: 1, balance: 0, version: 0);

        return new User(
            id: 1,
            name: 'John Doe',
            document: '123.456.789-00',
            email: 'john@example.com',
            type: UserType::COMMON,
            wallet: $wallet,
        );
    }

    private function makeDTO(): CreateUserDTO
    {
        return new CreateUserDTO(
            name: 'John Doe',
            document: '123.456.789-00',
            email: 'john@example.com',
            password: 'secret123',
            type: UserType::COMMON,
        );
    }

    public function testHappyPathCreatesAndReturnsUser(): void
    {
        $user = $this->makeUser();
        $dto = $this->makeDTO();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByDocument')->with($dto->document)->willReturn(null);
        $repository->method('findByEmail')->with($dto->email)->willReturn(null);
        $repository->expects($this->once())
            ->method('create')
            ->willReturn($user);

        $useCase = new CreateUserUseCase($repository);
        $result = $useCase->execute($dto);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame(1, $result->getId());
        $this->assertSame('John Doe', $result->getName());
    }

    public function testThrowsDuplicateDocumentExceptionWhenDocumentAlreadyExists(): void
    {
        $dto = $this->makeDTO();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByDocument')->with($dto->document)->willReturn($this->makeUser());

        $useCase = new CreateUserUseCase($repository);

        $this->expectException(DuplicateDocumentException::class);

        $useCase->execute($dto);
    }

    public function testThrowsDuplicateEmailExceptionWhenEmailAlreadyExists(): void
    {
        $dto = $this->makeDTO();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByDocument')->with($dto->document)->willReturn(null);
        $repository->method('findByEmail')->with($dto->email)->willReturn($this->makeUser());

        $useCase = new CreateUserUseCase($repository);

        $this->expectException(DuplicateEmailException::class);

        $useCase->execute($dto);
    }
}
