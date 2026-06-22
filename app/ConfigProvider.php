<?php

declare(strict_types=1);

namespace App;

use App\Application\Services\AuthorizerService;
use App\Application\Services\NotificationService;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Infrastructure\Repositories\TransferRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\WalletRepository;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                UserRepositoryInterface::class => UserRepository::class,
                WalletRepositoryInterface::class => WalletRepository::class,
                TransferRepositoryInterface::class => TransferRepository::class,
            ],
            'commands' => [],
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
