<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit\Reference\TagReference\Resolve;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Reference\TagReference;

final class TagReferenceResolveTest extends TestCase
{
    public function testBase(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    Main::class => [
                        '$data' => TagReference::to('letters'),
                    ],
                ])
                ->withTags([
                    'letters' => [A::class, B::class],
                ])
        );

        $main = $container->get(Main::class);

        $this->assertCount(2, $main->data);
        $this->assertInstanceOf(A::class, $main->data[0]);
        $this->assertInstanceOf(B::class, $main->data[1]);
    }
}
