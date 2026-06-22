<?php

declare(strict_types=1);

use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Infrastructure\Repositories\TransferRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\WalletRepository;

return [
    UserRepositoryInterface::class     => UserRepository::class,
    WalletRepositoryInterface::class   => WalletRepository::class,
    TransferRepositoryInterface::class => TransferRepository::class,
];
