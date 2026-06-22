<?php

declare(strict_types=1);

namespace App\Interfaces\Console;

use App\Infrastructure\Cache\WalletBalanceCache;
use App\Infrastructure\Persistence\Models\UserModel;
use App\Infrastructure\Persistence\Models\WalletModel;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
final class WalletBalanceCommand extends HyperfCommand
{
    protected ?string $name = 'wallet:balance';

    public function __construct(private readonly WalletBalanceCache $cache)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Show current wallet balance for a user');
        $this->addArgument('userId', InputArgument::REQUIRED, 'User ID');
        $this->addOption('fresh', null, InputOption::VALUE_NONE, 'Bypass cache and read from database');
    }

    public function handle(): void
    {
        $userId = (int) $this->input->getArgument('userId');
        $fresh = (bool) $this->input->getOption('fresh');

        $user = UserModel::find($userId);
        if ($user === null) {
            $this->error("User {$userId} not found.");
            return;
        }

        if (! $fresh) {
            $cached = $this->cache->get($userId);
            if ($cached !== null) {
                $this->line("Balance for user {$userId}: <info>{$cached}</info> (from cache)");
                return;
            }
        }

        $wallet = WalletModel::where('user_id', $userId)->first();
        $balance = $wallet ? (float) $wallet->balance : 0.0;

        $this->cache->set($userId, $balance);
        $this->line("Balance for user {$userId}: <info>{$balance}</info> (from database)");
    }
}
