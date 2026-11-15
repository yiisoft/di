<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\CompositeNotFoundException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\NonPsrContainer;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

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

    public function testHasStateResetter(): void
    {
        $container = new CompositeContainer();

        assertTrue($container->has(StateResetter::class));
    }

    /**
     * According to PSR-11, two successive calls to `get()` with the same identifier should return the same object.
     */
    public function testGetStateResetterTwice(): void
    {
        $compositeContainer = new CompositeContainer();
        $compositeContainer->attach(new Container(ContainerConfig::create()));

        $resetter1 = $compositeContainer->get(StateResetter::class);
        $resetter2 = $compositeContainer->get(StateResetter::class);

        assertInstanceOf(StateResetter::class, $resetter1);
        assertSame($resetter1, $resetter2);
    }

    public function testAttachedContainerResetterIsRetainedBetweenResets(): void
    {
        $engine = new EngineMarkOne();
        $engine->setNumber(42);

        $compositeContainer = new CompositeContainer();
        $compositeContainer->attach(
            new class ($engine) implements ContainerInterface {
                public function __construct(private readonly EngineMarkOne $engine) {}

                public function get($id)
                {
                    if ($id === 'engine') {
                        return $this->engine;
                    }

                    if ($id === StateResetter::class) {
                        return new StateResetter($this, [
                            'engine' => function (): void {
                                // The callback caches the initial number on the first reset
                                // and restores it on every subsequent reset.
                                static $initialNumber = null;
                                $initialNumber ??= $this->number;
                                $this->number = $initialNumber;
                            },
                        ]);
                    }

                    throw new RuntimeException('Unknown ID: ' . $id);
                }

                public function has($id): bool
                {
                    return $id === 'engine' || $id === StateResetter::class;
                }
            },
        );

        $resetter = $compositeContainer->get(StateResetter::class);

        $resetter->reset();
        assertSame(42, $engine->getNumber());

        $engine->setNumber(99);

        $resetter->reset();
        assertSame(42, $engine->getNumber());
    }

    public function testResetterResetsContainerAttachedAfterResetterIsObtained(): void
    {
        $compositeContainer = new CompositeContainer();

        $firstContainer = new Container(
            ContainerConfig::create()->withDefinitions([
                'engineMarkOne' => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                    'reset' => function () {
                        $this->number = 42;
                    },
                ],
            ]),
        );
        $secondContainer = new Container(
            ContainerConfig::create()->withDefinitions([
                'engineMarkTwo' => [
                    'class' => EngineMarkTwo::class,
                    'setNumber()' => [43],
                    'reset' => function () {
                        $this->number = 43;
                    },
                ],
            ]),
        );

        $compositeContainer->attach($firstContainer);
        $resetter = $compositeContainer->get(StateResetter::class);

        $compositeContainer->attach($secondContainer);

        $engineMarkOne = $compositeContainer->get('engineMarkOne');
        $engineMarkTwo = $compositeContainer->get('engineMarkTwo');
        $engineMarkOne->setNumber(45);
        $engineMarkTwo->setNumber(46);

        $resetter->reset();

        assertSame(42, $engineMarkOne->getNumber());
        assertSame(43, $engineMarkTwo->getNumber());
    }

    public function testResetterDoesNotResetDetachedContainer(): void
    {
        $compositeContainer = new CompositeContainer();

        $firstContainer = new Container(
            ContainerConfig::create()->withDefinitions([
                'engineMarkOne' => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                    'reset' => function () {
                        $this->number = 42;
                    },
                ],
            ]),
        );
        $secondContainer = new Container(
            ContainerConfig::create()->withDefinitions([
                'engineMarkTwo' => [
                    'class' => EngineMarkTwo::class,
                    'setNumber()' => [43],
                    'reset' => function () {
                        $this->number = 43;
                    },
                ],
            ]),
        );

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);
        $resetter = $compositeContainer->get(StateResetter::class);

        $engineMarkOne = $compositeContainer->get('engineMarkOne');
        $engineMarkTwo = $compositeContainer->get('engineMarkTwo');

        $compositeContainer->detach($secondContainer);

        $engineMarkOne->setNumber(45);
        $engineMarkTwo->setNumber(46);

        $resetter->reset();

        assertSame(42, $engineMarkOne->getNumber());
        assertSame(46, $engineMarkTwo->getNumber());
    }
}
