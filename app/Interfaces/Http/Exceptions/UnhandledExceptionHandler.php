<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Exceptions;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class UnhandledExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();

        return $response
            ->withStatus(500)
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream((string) json_encode([
                'message' => 'Internal Server Error',
                'error' => $throwable->getMessage(),
                'class' => get_class($throwable),
            ])));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
