<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\NullableConcreteDependency;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Factory\Definition\Reference;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @BeforeMethods({"before"})
 */
class ContainerBench
{
    public const SERVICE_COUNT = 200;

    private CompositeContainer $composite;

    /** @var int[] */
    private array $indexes = [];

    /** @var int[] */
    private array $randomIndexes = [];

    public function provideDefinitions(): array
    {
        return [
            ['serviceClass' => PropertyTestClass::class],
            [
                'serviceClass' => NullableConcreteDependency::class,
                'otherDefinitions' => [
                    EngineInterface::class => EngineMarkOne::class,
                    Car::class => Car::class,
                    EngineMarkOne::class => EngineMarkOne::class,
                ],
            ],
            [
                'serviceClass' => NullableConcreteDependency::class,
                'otherDefinitions' => [
                    EngineInterface::class => EngineMarkTwo::class,
                ],
            ],
        ];
    }

    /**
     * Load the bulk of the definitions.
     * These all refer to a service that is not yet defined but must be defined in the bench.
     */
    public function before(): void
    {
        $definitions3 = [];
        $definitions2 = [];
        $definitions3['service'] = PropertyTestClass::class;
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $this->indexes[] = $i;
            $definitions2["second$i"] = Reference::to('service');
            $definitions3["third$i"] = Reference::to('service');
        }
        $this->randomIndexes = $this->indexes;
        shuffle($this->randomIndexes);

        $this->composite = new CompositeContainer();
        // We attach the dummy containers multiple times, to see what would happen if we have lots of them.
        $this->composite->attach(new Container($definitions2));
        $this->composite->attach(new Container($definitions3));
        $this->composite->attach(new Container($definitions2));
        $this->composite->attach(new Container($definitions3));
        $this->composite->attach(new Container($definitions2));
        $this->composite->attach(new Container($definitions3));
        $this->composite->attach(new Container($definitions2));
        $this->composite->attach(new Container($definitions3));
    }

    /**
     * @Groups({"construct"})
     *
     * @throws \Yiisoft\Factory\Exception\InvalidConfigException
     * @throws \Yiisoft\Factory\Exception\NotInstantiableException
     */
    public function benchConstruct(): void
    {
        $definitions = [];
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = PropertyTestClass::class;
        }
        $container = new Container($definitions);
    }

    /**
     * @Groups({"lookup"})
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchSequentialLookups($params): void
    {
        $definitions = [];
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = $params['serviceClass'];
        }
        if (isset($params['otherDefinitions'])) {
            $definitions = array_merge($definitions, $params['otherDefinitions']);
        }
        $container = new Container($definitions);
        for ($i = 0; $i < self::SERVICE_COUNT / 2; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            $container->get("service$index");
        }
    }

    /**
     * @Groups({"lookup"})
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookups($params): void
    {
        $definitions = [];
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = $params['serviceClass'];
        }
        if (isset($params['otherDefinitions'])) {
            $definitions = array_merge($definitions, $params['otherDefinitions']);
        }
        $container = new Container($definitions);
        for ($i = 0; $i < self::SERVICE_COUNT / 2; $i++) {
            // Do array lookup.
            $index = $this->randomIndexes[$i];
            $container->get("service$index");
        }
    }

    /**
     * @Groups({"lookup"})
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookupsComposite($params): void
    {
        $definitions = [];
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = $params['serviceClass'];
        }
        if (isset($params['otherDefinitions'])) {
            $definitions = array_merge($definitions, $params['otherDefinitions']);
        }
        $container = new Container($definitions);
        $this->composite->attach($container);
        for ($i = 0; $i < self::SERVICE_COUNT / 2; $i++) {
            // Do array lookup.
            $index = $this->randomIndexes[$i];
            $this->composite->get("service$index");
        }
    }
}
