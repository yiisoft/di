<?php

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;

class CompositeContainerTest extends TestCase
{
    public function testFallbackContainer(): void
    {
        $container = new CompositeContainer();
        $container->attach(
            new Container(
                [
                    'first' => function () {
                        return 'first';
                    },
                ]
            )
        );
        $container->attach(
            new Container(
                [
                    'second' => function () {
                        return 'second';
                    },
                    'third' => function ($c) {
                        return $c->get('first') . $c->get('second') . 'third';
                    },
                ]
            )
        );
        $this->assertSame('first', $container->get('first'));
        $this->assertSame('second', $container->get('second'));
        $this->assertSame('firstsecondthird', $container->get('third'));
    }
}
