<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Tests\Support\EngineMarkOne;

final class CompositeContainerTest extends TestCase
{
    public function testGetNonString(): void
    {
        $container = new CompositeContainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Id must be a string, integer given.'
        );
        $container->get(42);
    }

    public function testTagsWithYiiAndNotYiiContainers(): void
    {
        $compositeContainer = new CompositeContainer();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['engine'],
                ],
            ]);
        $firstContainer = new Container($config);

        $secondContainer = new \League\Container\Container();

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $engines = $compositeContainer->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(1, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
    }
}
