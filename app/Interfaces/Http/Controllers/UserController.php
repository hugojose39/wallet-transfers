<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\DTOs\CreateUserDTO;
use App\Application\UseCases\CreateUserUseCase;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Domain\User\Enums\UserType;
use App\Infrastructure\Cache\WalletBalanceCache;
use App\Interfaces\Http\Requests\StoreDepositRequest;
use App\Interfaces\Http\Requests\StoreUserRequest;
use App\Interfaces\Http\Resources\TransferResource;
use App\Interfaces\Http\Resources\UserResource;
use App\Interfaces\Http\Resources\WalletResource;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class UserController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransferRepositoryInterface $transferRepository,
        private readonly WalletBalanceCache $balanceCache,
        private readonly CreateUserUseCase $useCase,
    ) {
    }

    public function store(StoreUserRequest $request, ResponseInterface $response): PsrResponseInterface
    {
        $dto = new CreateUserDTO(
            name: $request->input('name'),
            document: $request->input('document'),
            email: $request->input('email'),
            password: $request->input('password'),
            type: UserType::from($request->input('type')),
        );

        $user = $this->useCase->execute($dto);

        return $response->json([
            'data' => (new UserResource($user))->toArray(),
        ])->withStatus(201);
    }

    public function transfers(int $id, RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return $response->json(['message' => 'User not found.'])->withStatus(404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $transfers = $this->transferRepository->findByUserId($id, $page);

        return $response->json([
            'data' => array_map(fn ($t) => (new TransferResource($t))->toArray(), $transfers),
            'meta' => ['page' => $page],
        ]);
    }

    public function deposit(int $id, StoreDepositRequest $request, ResponseInterface $response): PsrResponseInterface
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return $response->json(['message' => 'User not found.'])->withStatus(404);
        }

        $amount = (int) round((float) $request->input('amount') * 100);

        $wallet = Db::transaction(function () use ($id, $amount) {
            $wallet = $this->walletRepository->findByUserIdForUpdate($id);
            $wallet->credit($amount);
            $this->walletRepository->save($wallet);
            return $wallet;
        });

        $this->balanceCache->set($id, $wallet->getBalance());

        return $response->json([
            'data' => (new WalletResource($wallet, false))->toArray(),
        ]);
    }

    public function wallet(int $id, ResponseInterface $response): PsrResponseInterface
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return $response->json(['message' => 'User not found.'])->withStatus(404);
        }

        $cached = $this->balanceCache->get($id);
        $fromCache = $cached !== null;

        if (!$fromCache) {
            $this->balanceCache->set($id, $user->getWallet()->getBalance());
        }

        return $response->json([
            'data' => (new WalletResource($user->getWallet(), $fromCache))->toArray(),
        ]);
    }
}
