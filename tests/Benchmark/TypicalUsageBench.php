<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Reference\TagReference;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarFactory;
use Yiisoft\Di\Tests\Support\ColorInterface;
use Yiisoft\Di\Tests\Support\ColorRed;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @BeforeMethods({"before"})
 */
class TypicalUsageBench
{
    private Container $cachedServiceContainer;

    public function before(): void
    {
        $this->cachedServiceContainer = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                    Car::class => Car::class,
                ]),
        );
        $this->cachedServiceContainer->get(Car::class);
    }

    /**
     * Measures the hot path after the shared service was already built.
     *
     * @Groups({"lookup", "typical"})
     */
    public function benchCachedSharedService(): void
    {
        $this->cachedServiceContainer->get(Car::class);
    }

    /**
     * Measures first resolution of an autowired object graph by class name.
     *
     * @Groups({"lookup", "autowire", "typical"})
     * @Revs(100)
     */
    public function benchAutowireObjectGraph(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                ]),
        );

        $container->get(Car::class);
    }

    /**
     * Measures first resolution of an autowired object graph by class name without eager definition validation.
     *
     * @Groups({"lookup", "autowire", "typical", "no-validation"})
     * @Revs(100)
     */
    public function benchAutowireObjectGraphWithoutValidation(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withValidate(false)
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                ]),
        );

        $container->get(Car::class);
    }

    /**
     * Measures explicit array definitions with constructor, setter, and reference resolution.
     *
     * @Groups({"lookup", "definition", "typical"})
     * @Revs(100)
     */
    public function benchArrayDefinitionObjectGraph(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                    ColorInterface::class => ColorRed::class,
                    'car' => [
                        'class' => Car::class,
                        '__construct()' => [Reference::to(EngineInterface::class)],
                        'setColor()' => [Reference::to(ColorInterface::class)],
                    ],
                ]),
        );

        $container->get('car');
    }

    /**
     * Measures explicit array definitions with constructor, setter, and reference resolution without eager validation.
     *
     * @Groups({"lookup", "definition", "typical", "no-validation"})
     * @Revs(100)
     */
    public function benchArrayDefinitionObjectGraphWithoutValidation(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withValidate(false)
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                    ColorInterface::class => ColorRed::class,
                    'car' => [
                        'class' => Car::class,
                        '__construct()' => [Reference::to(EngineInterface::class)],
                        'setColor()' => [Reference::to(ColorInterface::class)],
                    ],
                ]),
        );

        $container->get('car');
    }

    /**
     * Measures first resolution of a callable factory definition.
     *
     * @Groups({"lookup", "factory", "typical"})
     * @Revs(100)
     */
    public function benchFactoryDefinition(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    ColorInterface::class => ColorRed::class,
                    'car' => [CarFactory::class, 'createWithColor'],
                ]),
        );

        $container->get('car');
    }

    /**
     * Measures first resolution of a callable factory definition without eager validation.
     *
     * @Groups({"lookup", "factory", "typical", "no-validation"})
     * @Revs(100)
     */
    public function benchFactoryDefinitionWithoutValidation(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withValidate(false)
                ->withDefinitions([
                    ColorInterface::class => ColorRed::class,
                    'car' => [CarFactory::class, 'createWithColor'],
                ]),
        );

        $container->get('car');
    }

    /**
     * Measures collecting all services registered under a tag.
     *
     * @Groups({"lookup", "tag", "typical"})
     * @Revs(100)
     */
    public function benchTaggedServices(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    EngineMarkOne::class => [
                        'class' => EngineMarkOne::class,
                        'tags' => ['engine'],
                    ],
                    EngineMarkTwo::class => [
                        'class' => EngineMarkTwo::class,
                        'tags' => ['engine'],
                    ],
                    'engine-reference' => [
                        'definition' => Reference::to(EngineMarkOne::class),
                        'tags' => ['engine'],
                    ],
                ]),
        );

        $container->get(TagReference::id('engine'));
    }

    /**
     * Measures collecting all services registered under a tag without eager validation.
     *
     * @Groups({"lookup", "tag", "typical", "no-validation"})
     * @Revs(100)
     */
    public function benchTaggedServicesWithoutValidation(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withValidate(false)
                ->withDefinitions([
                    EngineMarkOne::class => [
                        'class' => EngineMarkOne::class,
                        'tags' => ['engine'],
                    ],
                    EngineMarkTwo::class => [
                        'class' => EngineMarkTwo::class,
                        'tags' => ['engine'],
                    ],
                    'engine-reference' => [
                        'definition' => Reference::to(EngineMarkOne::class),
                        'tags' => ['engine'],
                    ],
                ]),
        );

        $container->get(TagReference::id('engine'));
    }

    /**
     * Measures fallback through delegates configured on the container.
     *
     * @Groups({"lookup", "delegate", "typical"})
     * @Revs(100)
     */
    public function benchDelegateFallback(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDelegates([
                    static fn(ContainerInterface $container): ContainerInterface => new Container(
                        ContainerConfig::create()
                            ->withDefinitions([
                                EngineInterface::class => EngineMarkOne::class,
                            ]),
                    ),
                ]),
        );

        $container->get(EngineInterface::class);
    }

    /**
     * Measures fallback through delegates configured on the container without eager validation.
     *
     * @Groups({"lookup", "delegate", "typical", "no-validation"})
     * @Revs(100)
     */
    public function benchDelegateFallbackWithoutValidation(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withValidate(false)
                ->withDelegates([
                    static fn(ContainerInterface $container): ContainerInterface => new Container(
                        ContainerConfig::create()
                            ->withValidate(false)
                            ->withDefinitions([
                                EngineInterface::class => EngineMarkOne::class,
                            ]),
                    ),
                ]),
        );

        $container->get(EngineInterface::class);
    }
}
