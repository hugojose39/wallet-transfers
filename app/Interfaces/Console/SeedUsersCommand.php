<?php

declare(strict_types=1);

namespace App\Interfaces\Console;

use App\Infrastructure\Persistence\Models\UserModel;
use App\Infrastructure\Persistence\Models\WalletModel;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Input\InputOption;

#[Command]
final class SeedUsersCommand extends HyperfCommand
{
    protected ?string $name = 'seed:users';

    public function __construct()
    {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Seed common and merchant users with initial balance');
        $this->addOption('common', null, InputOption::VALUE_OPTIONAL, 'Number of common users', 5);
        $this->addOption('merchant', null, InputOption::VALUE_OPTIONAL, 'Number of merchant users', 2);
        $this->addOption('balance', null, InputOption::VALUE_OPTIONAL, 'Initial balance per user', 1000);
    }

    public function handle(): void
    {
        $commonCount = (int) $this->input->getOption('common');
        $merchantCount = (int) $this->input->getOption('merchant');
        $balance = (float) $this->input->getOption('balance');

        $this->line("Seeding {$commonCount} common users and {$merchantCount} merchant users with balance {$balance}...");

        Db::transaction(function () use ($commonCount, $merchantCount, $balance) {
            for ($i = 1; $i <= $commonCount; $i++) {
                $user = UserModel::create([
                    'name' => "Common User {$i}",
                    'document' => $this->generateCpf($i),
                    'email' => "common{$i}@wallet.test",
                    'password' => password_hash('secret', PASSWORD_BCRYPT),
                    'type' => 'common',
                ]);

                WalletModel::create(['user_id' => $user->id, 'balance' => $balance]);
                $this->info("Created common user ID={$user->id}");
            }

            for ($i = 1; $i <= $merchantCount; $i++) {
                $user = UserModel::create([
                    'name' => "Merchant {$i}",
                    'document' => $this->generateCnpj($i),
                    'email' => "merchant{$i}@wallet.test",
                    'password' => password_hash('secret', PASSWORD_BCRYPT),
                    'type' => 'merchant',
                ]);

                WalletModel::create(['user_id' => $user->id, 'balance' => $balance]);
                $this->info("Created merchant user ID={$user->id}");
            }
        });

        $this->line('Done.');
    }

    private function generateCpf(int $seed): string
    {
        return sprintf('%03d.%03d.%03d-%02d', $seed, $seed * 2, $seed * 3, $seed % 100);
    }

    private function generateCnpj(int $seed): string
    {
        return sprintf('%02d.%03d.%03d/%04d-%02d', $seed, $seed * 2, $seed * 3, 1, $seed % 100);
    }
}
