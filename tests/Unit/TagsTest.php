<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Factory\Definitions\TagDefinition;
use Yiisoft\Factory\Exceptions\NotFoundException;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Factory\Exceptions\InvalidConfigException;

/**
 * ContainerTest contains tests for \Yiisoft\Di\Container
 */
class TagsTest extends TestCase
{
    public function testTagNameAlreadyInUse(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Container([
            'engine' => EngineMarkOne::class,
            EngineMarkOne::class => [
                '__tags' => ['engine'],
            ],
        ]);
    }

    public function testTagsInArrayDefinition(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                '__tags' => ['#engines'],
            ],
            EngineMarkTwo::class => [
                '__class' => EngineMarkTwo::class,
                '__tags' => ['#engines'],
            ],
        ]);

        $engines = $container->get('#engines');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
    }

    public function testTagsInClosureDefinition(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                '__definition' => fn () => new EngineMarkOne(),
                '__tags' => ['#engine'],
            ],
            EngineMarkTwo::class => [
                '__definition' => fn () => new EngineMarkTwo(),
                '__tags' => ['#engine'],
            ],
        ]);

        $engines = $container->get('#engine');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
    }

    public function testTagsMultiple(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                '__tags' => ['#engine', '#mark-one'],
            ],
            EngineMarkTwo::class => [
                '__tags' => ['#engine'],
            ],
        ]);

        $engines = $container->get('#engine');
        $markOne = $container->get('#mark-one');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
        $this->assertIsArray($markOne);
        $this->assertInstanceOf(EngineMarkOne::class, $markOne[0]);
        $this->assertCount(1, $markOne);
    }

    public function testTagsEmpty(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                '__class' => EngineMarkOne::class,
            ],
            EngineMarkTwo::class => [
                '__class' => EngineMarkTwo::class,
            ],
        ]);

        $this->expectException(NotFoundException::class);
        $container->get('#engine');
    }

    public function testTagsWithExternalDefinition(): void
    {
        $container = new Container([
            '#mark-two' => new TagDefinition([
                EngineMarkTwo::class,
            ]),
            EngineMarkOne::class => [
                '__class' => EngineMarkOne::class,
                '__tags' => ['#engines'],
            ],
            EngineMarkTwo::class => [
                '__class' => EngineMarkTwo::class,
                '__tags' => ['#engines'],
            ],
        ]);

        $engines = $container->get('#engines');
        $markTwo = $container->get('#mark-two');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
        $this->assertIsArray($markTwo);
        $this->assertInstanceOf(EngineMarkTwo::class, $markTwo[0]);
        $this->assertCount(1, $markTwo);
    }

    public function testTagsWithExternalDefinitionMerge(): void
    {
        $container = new Container([
            '#engines' => new TagDefinition([
                EngineMarkOne::class,
            ]),
            EngineMarkOne::class => [
                '__class' => EngineMarkOne::class,
            ],
            EngineMarkTwo::class => [
                '__class' => EngineMarkTwo::class,
                '__tags' => ['#engines'],
            ],
        ]);

        $engines = $container->get('#engines');
        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
    }

    public function testTagsUseTags(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            EngineMarkOne::class => [
                '__class' => EngineMarkOne::class,
                '__tags' => ['#engines'],
            ],
            EngineMarkTwo::class => [
                '__class' => EngineMarkTwo::class,
                '__tags' => ['#engines'],
            ],
            Car::class => [
                '__construct()' => [
                    'moreEngines' => Reference::to('#engines'),
                ],
            ],
        ]);

        $engines = $container->get(Car::class)->getMoreEngines();
        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
    }

    public function testTagsInCompositeContainer(): void
    {
        $firstContainer = new Container([
            EngineMarkOne::class => [
                '__class' => EngineMarkOne::class,
                '__tags' => ['#engines'],
            ],
        ]);
        $secondContainer = new Container([
            EngineMarkTwo::class => [
                '__class' => EngineMarkTwo::class,
                '__tags' => ['#engines'],
            ],
        ]);
        $compositeContainer = new CompositeContainer();
        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $engines = $compositeContainer->get('#engines');
        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[1]);
    }
}
