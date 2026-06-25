<?php

declare(strict_types=1);

namespace App\Interfaces\Console;

use App\Application\DTOs\TransferDTO;
use App\Application\UseCases\CreateTransferUseCase;
use App\Infrastructure\Persistence\Models\UserModel;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Coroutine\Parallel;
use Symfony\Component\Console\Input\InputOption;

#[Command]
final class SimulateTransferCommand extends HyperfCommand
{
    protected ?string $name = 'transfer:simulate';

    public function __construct(
        private readonly CreateTransferUseCase $useCase
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();

        $this->setDescription('Simulate N concurrent transfers between seeded users');

        $this->addOption(
            'count',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of transfers',
            10
        );

        $this->addOption(
            'concurrent',
            null,
            InputOption::VALUE_OPTIONAL,
            'Concurrent coroutines',
            5
        );
    }

    public function handle(): void
    {
        $count = (int)$this->input->getOption('count');
        $concurrent = (int)$this->input->getOption('concurrent');

        $commonUsers = UserModel::where('type', 'common')
            ->pluck('id')
            ->toArray();

        $allUsers = UserModel::pluck('id')->toArray();

        if (count($commonUsers) < 1 || count($allUsers) < 2) {
            $this->error('Not enough users. Run seed:users first.');
            return;
        }

        $this->line(
            "Simulating {$count} transfers with {$concurrent} concurrent coroutines..."
        );

        $parallel = new Parallel($concurrent);

        $success = 0;
        $failed = 0;

        for ($i = 0; $i < $count; $i++) {
            $parallel->add(function () use (
                $commonUsers,
                $allUsers,
                &$success,
                &$failed
            ) {
                try {
                    $payer = $commonUsers[array_rand($commonUsers)];

                    $payees = array_filter(
                        $allUsers,
                        fn ($id) => $id !== $payer
                    );

                    $payee = array_values($payees)[array_rand($payees)];

                    $this->useCase->execute(
                        new TransferDTO(
                            $payer,
                            $payee,
                            100
                        )
                    );

                    $success++;
                } catch (\Throwable) {
                    $failed++;
                }
            });
        }

        $parallel->wait();

        $this->info(
            "Success: {$success} | Failed: {$failed}"
        );
    }
}
