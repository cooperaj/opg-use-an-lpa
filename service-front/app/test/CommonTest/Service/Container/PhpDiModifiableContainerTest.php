<?php

declare(strict_types=1);

namespace CommonTest\Service\Container;

use Common\Service\Container\PhpDiModifiableContainer;
use DI\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class PhpDiModifiableContainerTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_only_acts_on_a_phpdi_container()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $di = new PhpDiModifiableContainer($containerProphecy->reveal());
    }

    /** @test */
    public function it_decorates_set_on_a_phpdi_container()
    {
        $containerProphecy = $this->prophesize(Container::class);
        $containerProphecy->set('test', 'test')->shouldBeCalled();

        $di = new PhpDiModifiableContainer($containerProphecy->reveal());

        $di->setValue('test', 'test');
    }
}
