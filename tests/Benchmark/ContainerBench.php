<?php
namespace Yiisoft\Di\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Factory\Tests\Support\NullableConcreteDependency;

/**
 * @Iterations(5)
 * @BeforeMethods({"before"})
 */
class ContainerBench
{
    /** @var Container */
    private $container;

    /** @var CompositeContainer */
    private $composite;

    /** @var int[] */
    private $indexes = [];

    public function provideDefinitions(): array
    {
        return [
            ['serviceClass' => PropertyTestClass::class],
            ['serviceClass' => NullableConcreteDependency::class, 'otherDefinitions' => [Car::class => Car::class]]
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
        for ($i = 0; $i < 1000; $i++) {
            $this->indexes[] = $i;
            $definitions["service$i"] = Reference::to('service');
            $definitions2["second$i"] = Reference::to('service');
            $definitions3["third$i"] = Reference::to('service');
        }
        $this->container = new Container($definitions);

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
        $this->composite->attach($this->container);

        shuffle($this->indexes);
    }
    /**
     * @Revs(1000)
     */
    public function benchConstructStupid(): void
    {
        $container = new Container();
        for ($i = 0; $i < 1000; $i++) {
            $container->set("service$i", PropertyTestClass::class);
        }
    }

    /**
     * @Revs(1000)
     */
    public function benchConstructSmart(): void
    {
        $definitions = [];
        for ($i = 0; $i < 1000; $i++) {
            $definitions["service$i"] = PropertyTestClass::class;
        }
        $container = new Container($definitions);
    }

    /**
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchSequentialLookups($params): void
    {
        $this->container->set('service', $params['serviceClass']);
        if (isset($params['otherDefinitions'])) {
            $this->container->setMultiple($params['otherDefinitions']);
        }
        for ($i = 0; $i < 1000; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            try {
                $this->container->get("service$i");
            } catch (\Exception $e) {
                // Skip exceptions
            }
        }
    }

    /**
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookups($params): void
    {
        $this->container->set('service', $params['serviceClass']);
        if (isset($params['otherDefinitions'])) {
            $this->container->setMultiple($params['otherDefinitions']);
        }
        for ($i = 0; $i < 1000; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            try {
                $this->container->get("service$index");
            } catch (\Exception $e) {
                // Skip exceptions
            }
        }
    }

    /**
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookupsComposite($params): void
    {
        $this->container->set('service', $params['serviceClass']);
        if (isset($params['otherDefinitions'])) {
            $this->container->setMultiple($params['otherDefinitions']);
        }
        for ($i = 0; $i < 1000; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            try {
                $this->composite->get("service$index");
            } catch (\Exception $e) {
                // Skip exceptions
            }
        }
    }
}
