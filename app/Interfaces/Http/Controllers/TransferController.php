<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\DTOs\TransferDTO;
use App\Application\UseCases\CreateTransferUseCase;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Interfaces\Http\Requests\StoreTransferRequest;
use App\Interfaces\Http\Resources\TransferResource;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class TransferController
{
    public function __construct(
        private readonly CreateTransferUseCase $useCase,
        private readonly TransferRepositoryInterface $transferRepository,
    ) {
    }

    public function store(StoreTransferRequest $request, ResponseInterface $response): PsrResponseInterface
    {
        $dto = new TransferDTO(
            payerId: (int) $request->input('payer'),
            payeeId: (int) $request->input('payee'),
            amount: (int) round((float) $request->input('value') * 100),
        );

        try {
            $result = $this->useCase->execute($dto);

            return $response->json([
                'message' => 'Transfer completed successfully.',
                'data' => (new TransferResource($result->toEntity()))->toArray(),
            ])->withStatus(201);
        } catch (\RuntimeException $e) {
            return $response->json(['message' => $e->getMessage()])->withStatus(409);
        }
    }

    public function show(int $id, ResponseInterface $response): PsrResponseInterface
    {
        $transfer = $this->transferRepository->findById($id);

        if ($transfer === null) {
            return $response->json(['message' => 'Transfer not found.'])->withStatus(404);
        }

        return $response->json([
            'data' => (new TransferResource($transfer))->toArray(),
        ]);
    }
}
