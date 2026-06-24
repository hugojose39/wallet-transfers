<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Shared\Exceptions\UserNotFoundException;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Entities\User;
use App\Domain\User\Entities\Wallet;
use App\Domain\User\Enums\UserType;
use App\Infrastructure\Persistence\Models\UserModel;
use App\Infrastructure\Persistence\Models\WalletModel;
use Hyperf\DbConnection\Db;

final class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $model = UserModel::with('wallet')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByIdOrFail(int $id): User
    {
        $user = $this->findById($id);

        if ($user === null) {
            throw new UserNotFoundException("User with ID {$id} not found.");
        }

        return $user;
    }

    public function findByDocument(string $document): ?User
    {
        $model = UserModel::with('wallet')->where('document', $document)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::with('wallet')->where('email', $email)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function create(string $name, string $document, string $email, string $passwordHash, UserType $type): User
    {
        return Db::transaction(function () use ($name, $document, $email, $passwordHash, $type): User {
            $userModel = UserModel::create([
                'name' => $name,
                'document' => $document,
                'email' => $email,
                'password' => $passwordHash,
                'type' => $type->value,
            ]);

            $walletModel = WalletModel::create([
                'user_id' => $userModel->id,
                'balance' => 0,
            ]);

            $userModel->setRelation('wallet', $walletModel);

            return $this->toEntity($userModel);
        });
    }

    private function toEntity(UserModel $model): User
    {
        $walletModel = $model->wallet;

        $wallet = new Wallet(
            id: $walletModel->id,
            userId: $walletModel->user_id,
            balance: (int) $walletModel->balance,
        );

        return new User(
            id: $model->id,
            name: $model->name,
            document: $model->document,
            email: $model->email,
            type: UserType::from($model->type),
            wallet: $wallet,
        );
    }
}
