<?php

declare(strict_types=1);

namespace App;

use App\Application\Services\AuthorizerServiceInterface;
use App\Application\Services\AuthorizerService;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Infrastructure\Repositories\TransferRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\WalletRepository;
use App\Interfaces\Console\SeedUsersCommand;
use App\Interfaces\Console\SimulateTransferCommand;
use App\Interfaces\Console\TransferListCommand;
use App\Interfaces\Console\WalletBalanceCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                UserRepositoryInterface::class      => UserRepository::class,
                WalletRepositoryInterface::class    => WalletRepository::class,
                TransferRepositoryInterface::class  => TransferRepository::class,
                AuthorizerServiceInterface::class   => AuthorizerService::class,
            ],
            'commands' => [
                SeedUsersCommand::class,
                SimulateTransferCommand::class,
                WalletBalanceCommand::class,
                TransferListCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
