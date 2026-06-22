<?php

declare(strict_types=1);

namespace App\Interfaces\Console;

use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
final class TransferListCommand extends HyperfCommand
{
    protected ?string $name = 'transfer:list';

    public function __construct(private readonly TransferRepositoryInterface $transferRepository)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('List transfer history for a user');
        $this->addArgument('userId', InputArgument::REQUIRED, 'User ID');
    }

    public function handle(): void
    {
        $userId = (int) $this->input->getArgument('userId');
        $transfers = $this->transferRepository->findByUserId($userId);

        if (empty($transfers)) {
            $this->line("No transfers found for user {$userId}.");
            return;
        }

        $rows = array_map(fn ($t) => [
            $t->getId(),
            $t->getPayerId(),
            $t->getPayeeId(),
            number_format($t->getAmount(), 2),
            $t->getStatus()->value,
            $t->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $transfers);

        $this->table(['ID', 'Payer', 'Payee', 'Amount', 'Status', 'Created At'], $rows);
    }
}
