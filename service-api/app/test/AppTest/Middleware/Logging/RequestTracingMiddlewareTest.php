<?php

declare(strict_types=1);

namespace AppTest\Middleware\Logging;

use App\Middleware\Logging\RequestTracingMiddleware;
use App\Service\Container\ModifiableContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestTracingMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_sets_a_trace_attribute_if_set_as_a_header(): void
    {
        $containerProphecy = $this->prophesize(ModifiableContainerInterface::class);
        $containerProphecy->setValue('trace-id', 'Root=1-1-11')->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getHeader('x-amzn-trace-id')->willReturn(['Root=1-1-11']);
        $requestProphecy->withAttribute('trace-id', 'Root=1-1-11')->willReturn($requestProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rtm = new RequestTracingMiddleware($containerProphecy->reveal());
        $response = $rtm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function trace_id_is_blank_if_no_header(): void
    {
        $containerProphecy = $this->prophesize(ModifiableContainerInterface::class);
        $containerProphecy->setValue('trace-id', '')->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getHeader('x-amzn-trace-id')->willReturn([]);
        $requestProphecy->withAttribute('trace-id', '')->willReturn($requestProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $rtm = new RequestTracingMiddleware($containerProphecy->reveal());
        $response = $rtm->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
