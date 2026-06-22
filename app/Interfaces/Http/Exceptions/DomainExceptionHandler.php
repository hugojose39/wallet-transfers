<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class DomainExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();
        $body = json_encode([
            'message' => $throwable->getMessage(),
            'error_code' => $throwable->errorCode(),
        ]);
        return $response
            ->withStatus($throwable->httpStatusCode())
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream($body));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof DomainException;
    }
}
