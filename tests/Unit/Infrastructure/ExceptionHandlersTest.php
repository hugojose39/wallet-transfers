<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Domain\Shared\Exceptions\DuplicateDocumentException;
use App\Interfaces\Http\Exceptions\DomainExceptionHandler;
use App\Interfaces\Http\Exceptions\UnhandledExceptionHandler;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ExceptionHandlersTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockResponse(): ResponseInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withStatus')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->andReturnSelf();
        $response->shouldReceive('withBody')->andReturnSelf();
        return $response;
    }

    public function testDomainExceptionHandlerIsValidForDomainException(): void
    {
        $handler = new DomainExceptionHandler();
        $this->assertTrue($handler->isValid(new DuplicateDocumentException('dup')));
        $this->assertFalse($handler->isValid(new RuntimeException('other')));
    }

    public function testDomainExceptionHandlerHandle(): void
    {
        $handler = new DomainExceptionHandler();
        $exception = new DuplicateDocumentException('Duplicate document');
        $response = $this->mockResponse();

        $result = $handler->handle($exception, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testUnhandledExceptionHandlerIsValidForAnyThrowable(): void
    {
        $handler = new UnhandledExceptionHandler();
        $this->assertTrue($handler->isValid(new RuntimeException('any')));
        $this->assertTrue($handler->isValid(new \Error('error')));
    }

    public function testUnhandledExceptionHandlerHandle(): void
    {
        $handler = new UnhandledExceptionHandler();
        $exception = new RuntimeException('Boom');
        $response = $this->mockResponse();

        $result = $handler->handle($exception, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
