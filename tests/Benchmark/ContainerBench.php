<?php

namespace Yiisoft\Di\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Di\Tests\Support\NullableConcreteDependency;
use Yiisoft\Factory\Definitions\Reference;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @BeforeMethods({"before"})
 */
class ContainerBench
{
    const SERVICE_COUNT = 200;

    /** @var CompositeContainer */
    private $composite;

    /** @var int[] */
    private $indexes = [];

    /** @var int[] */
    private $randomIndexes = [];

    public function provideDefinitions(): array
    {
        return [
            ['serviceClass' => PropertyTestClass::class],
            ['serviceClass' => NullableConcreteDependency::class, 'otherDefinitions' => [Car::class => Car::class]],
            [
                'serviceClass' => NullableConcreteDependency::class,
                'otherDefinitions' => [
                    EngineMarkOne::class => EngineMarkOne::class,
                    EngineMarkTwo::class => EngineMarkTwo::class,
                ],
            ]
        ];
    }

    /**
     * Load the bulk of the definitions.
     * These all refer to a service that is not yet defined but must be defined in the bench.
     */
    public function before(): void
    {
        $definitions = [];
        $definitions2 = [];
        $definitions['service'] = PropertyTestClass::class;
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
     * @throws \Yiisoft\Factory\Exceptions\InvalidConfigException
     * @throws \Yiisoft\Factory\Exceptions\NotInstantiableException
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
        if (isset($params['otherDefinitions'])) {
            array_merge($definitions, $params['otherDefinitions']);
        }
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = $params['serviceClass'];
        }
        $container = new Container($definitions);
        for ($i = 0; $i < self::SERVICE_COUNT / 2; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            try {
                $container->get("service$index");
            } catch (\Exception $e) {
                // Skip exceptions
            }
        }
    }

    /**
     * @Groups({"lookup"})
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookups($params): void
    {
        $definitions = [];
        if (isset($params['otherDefinitions'])) {
            array_merge($definitions, $params['otherDefinitions']);
        }
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = $params['serviceClass'];
        }
        $container = new Container($definitions);
        for ($i = 0; $i < self::SERVICE_COUNT / 2; $i++) {
            // Do array lookup.
            $index = $this->randomIndexes[$i];
            try {
                $container->get("service$index");
            } catch (\Exception $e) {
                // Skip exceptions
            }
        }
    }

    /**
     * @Groups({"lookup"})
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookupsComposite($params): void
    {
        $definitions = [];
        if (isset($params['otherDefinitions'])) {
            array_merge($definitions, $params['otherDefinitions']);
        }
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = $params['serviceClass'];
        }
        $container = new Container($definitions);
        $this->composite->attach($container);
        for ($i = 0; $i < self::SERVICE_COUNT / 2; $i++) {
            // Do array lookup.
            $index = $this->randomIndexes[$i];
            try {
                $this->composite->get("service$index");
            } catch (\Exception $e) {
                // Skip exceptions
            }
        }
    }
}
