<?php

declare(strict_types=1);

namespace App\Interfaces\Console;

use App\Application\DTOs\TransferDTO;
use App\Application\UseCases\CreateTransferUseCase;
use App\Infrastructure\Persistence\Models\UserModel;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;

#[Command]
final class SimulateTransferCommand extends HyperfCommand
{
    protected ?string $name = 'transfer:simulate';

    public function __construct(private readonly CreateTransferUseCase $useCase)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Simulate N concurrent transfers between seeded users');
        $this->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Number of transfers', 10);
        $this->addOption('concurrent', null, InputOption::VALUE_OPTIONAL, 'Concurrent coroutines', 5);
    }

    public function handle(): void
    {
        $count = (int) $this->input->getOption('count');
        $concurrent = (int) $this->input->getOption('concurrent');

        $commonUsers = UserModel::where('type', 'common')->pluck('id')->toArray();
        $allUsers = UserModel::pluck('id')->toArray();

        if (count($commonUsers) < 1 || count($allUsers) < 2) {
            $this->error('Not enough users. Run seed:users first.');
            return;
        }

        $this->line("Simulating {$count} transfers with {$concurrent} concurrent coroutines...");

        $wg = new \Swoole\Coroutine\WaitGroup();
        $results = ['success' => 0, 'failed' => 0];
        $channel = new \Swoole\Coroutine\Channel($concurrent);

        \Swoole\Coroutine\run(function () use ($count, $commonUsers, $allUsers, $wg, &$results, $channel) {
            for ($i = 0; $i < $count; $i++) {
                $channel->push(1);
                $wg->add();

                \Swoole\Coroutine::create(function () use ($commonUsers, $allUsers, $wg, &$results, $channel) {
                    try {
                        $payer = $commonUsers[array_rand($commonUsers)];
                        $payees = array_filter($allUsers, fn ($id) => $id !== $payer);
                        $payee = array_values($payees)[array_rand($payees)];

                        $this->useCase->execute(new TransferDTO($payer, $payee, 100));
                        $results['success']++;
                    } catch (\Throwable) {
                        $results['failed']++;
                    } finally {
                        $channel->pop();
                        $wg->done();
                    }
                });
            }

            $wg->wait();
        });

        $this->info("Success: {$results['success']} | Failed: {$results['failed']}");
    }
}
