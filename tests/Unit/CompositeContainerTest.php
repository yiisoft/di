<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\CompositeNotFoundException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\NonPsrContainer;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;

final class CompositeContainerTest extends TestCase
{
    public function testGetNonString(): void
    {
        $container = new CompositeContainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/^ID must be a string, (integer|int) given\.$/',
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
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                    'tags' => ['engine'],
                ],
            ]);
        $firstContainer = new Container($config);

        $secondContainer = new \League\Container\Container();

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $engines = $compositeContainer->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertInstanceOf(EngineMarkOne::class, $engines[0]);
        $this->assertInstanceOf(EngineMarkTwo::class, $engines[1]);
    }

    public function testNonPsrContainer(): void
    {
        $compositeContainer = new CompositeContainer();

        $compositeContainer->attach(new NonPsrContainer());

        $this->expectException(CompositeNotFoundException::class);
        $this->expectExceptionMessageMatches(
            '/No definition or class found or resolvable in composite container/',
        );
        $this->expectExceptionMessageMatches(
            '/Container "has\(\)" returned false, but no exception was thrown from "get\(\)"\./',
        );
        $compositeContainer->get('test');
    }

    public function testHasNoString(): void
    {
        $container = new CompositeContainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must be a string, bool given.');
        $container->has(true);
    }

    #[TestWith([true, 'engine'])]
    #[TestWith([false, 'other'])]
    public function testHasTag(bool $expected, string $tag): void
    {
        $container = new CompositeContainer();

        $container->attach(
            new Container(
                ContainerConfig::create()->withTags(['engine' => []]),
            ),
        );

        assertSame($expected, $container->has('tag@' . $tag));
    }

    public function testHasTagWithoutYiiContainer(): void
    {
        $container = new CompositeContainer();

        $container->attach(new SimpleContainer());

        assertFalse($container->has('tag@engine'));
    }
}
