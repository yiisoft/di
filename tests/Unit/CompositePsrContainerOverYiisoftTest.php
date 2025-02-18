<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\CompositeNotFoundException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;

/**
 * Test the CompositeContainer over Yiisoft Container.
 */
final class CompositePsrContainerOverYiisoftTest extends CompositePsrContainerTestAbstract
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        $config = ContainerConfig::create()
            ->withDefinitions($definitions);
        $container = new Container($config);
        return $this->createCompositeContainer($container);
    }

    public function testResetterInCompositeContainerWithExternalResetter(): void
    {
        $composite = $this->createContainer([
            StateResetter::class => function (ContainerInterface $container) {
                $resetter = new StateResetter($container);
                $resetter->setResetters([
                    'engineMarkOne' => function () {
                        $this->number = 42;
                    },
                ]);
                return $resetter;
            },
            'engineMarkOne' => function () {
                $engine = new EngineMarkOne();
                $engine->setNumber(42);
                return $engine;
            },
        ]);
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engineMarkTwo' => ['class' => EngineMarkTwo::class,
                    'setNumber()' => [43],
                    'reset' => function () {
                        $this->number = 43;
                    },
                ],
            ]);
        $secondContainer = new Container($config);
        $composite->attach($secondContainer);

        $engineMarkOne = $composite->get('engineMarkOne');
        $engineMarkTwo = $composite->get('engineMarkTwo');
        $this->assertSame(
            42,
            $composite
                ->get('engineMarkOne')
                ->getNumber(),
        );
        $this->assertSame(
            43,
            $composite
                ->get('engineMarkTwo')
                ->getNumber(),
        );

        $engineMarkOne->setNumber(45);
        $engineMarkTwo->setNumber(46);
        $this->assertSame(
            45,
            $composite
                ->get('engineMarkOne')
                ->getNumber(),
        );
        $this->assertSame(
            46,
            $composite
                ->get('engineMarkTwo')
                ->getNumber(),
        );

        $composite
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engineMarkOne, $composite->get('engineMarkOne'));
        $this->assertSame($engineMarkTwo, $composite->get('engineMarkTwo'));
        $this->assertSame(
            42,
            $composite
                ->get('engineMarkOne')
                ->getNumber(),
        );
        $this->assertSame(
            43,
            $composite
                ->get('engineMarkTwo')
                ->getNumber(),
        );
    }

    public function testNotFoundException(): void
    {
        $compositeContainer = new CompositeContainer();

        $container1 = new Container();
        $container1Id = spl_object_id($container1);
        $container2 = new Container();
        $container2Id = spl_object_id($container2);

        $compositeContainer->attach($container1);
        $compositeContainer->attach($container2);

        $this->expectException(CompositeNotFoundException::class);
        $this->expectExceptionMessage("No definition or class found or resolvable in composite container:\n    1. Container Yiisoft\Di\Container #$container1Id: No definition or class found or resolvable for \"test\" while building it.\n    2. Container Yiisoft\Di\Container #$container2Id: No definition or class found or resolvable for \"test\" while building it.");
        $compositeContainer->get('test');
    }
}
