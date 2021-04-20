<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;

/**
 * Test the CompositeContainer over Yiisoft Container.
 */
class CompositePsrContainerOverYiisoftTest extends AbstractCompositePsrContainerTest
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        $container = new Container($definitions);
        return $this->createCompositeContainer($container);
    }

    public function testResetterInCompositeContainerWithExternalResetter(): void
    {
        $composite = $this->createContainer([
            StateResetter::class => function (ContainerInterface $container) {
                $resetter = new StateResetter([], $container);
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
        $secondContainer = new Container([
            'engineMarkTwo' => ['class' => EngineMarkTwo::class,
                'setNumber()' => [43],
                'reset' => function () {
                    $this->number = 43;
                },],]);
        $composite->attach($secondContainer);

        $engineMarkOne = $composite->get('engineMarkOne');
        $engineMarkTwo = $composite->get('engineMarkTwo');
        $this->assertSame(42, $composite->get('engineMarkOne')->getNumber());
        $this->assertSame(43, $composite->get('engineMarkTwo')->getNumber());

        $engineMarkOne->setNumber(45);
        $engineMarkTwo->setNumber(46);
        $this->assertSame(45, $composite->get('engineMarkOne')->getNumber());
        $this->assertSame(46, $composite->get('engineMarkTwo')->getNumber());

        $composite->get(StateResetter::class)->reset();

        $this->assertSame($engineMarkOne, $composite->get('engineMarkOne'));
        $this->assertSame($engineMarkTwo, $composite->get('engineMarkTwo'));
        $this->assertSame(42, $composite->get('engineMarkOne')->getNumber());
        $this->assertSame(43, $composite->get('engineMarkTwo')->getNumber());
    }
}
