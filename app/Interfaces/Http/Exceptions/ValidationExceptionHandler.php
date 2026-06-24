<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Exceptions;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ValidationExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();

        /** @var ValidationException $throwable */
        $errors = $throwable->validator->errors()->toArray();

        return $response
            ->withStatus(422)
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream((string) json_encode(['errors' => $errors])));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
