<?php


namespace yii\di\tests\benchmark;

use yii\di\CompositeContainer;
use yii\di\Container;
use yii\di\Reference;
use yii\di\tests\support\Car;
use yii\di\tests\support\NullableConcreteDependency;
use yii\di\tests\support\PropertyTestClass;

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

    public function provideDefinitions()
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
    public function before()
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
    public function benchConstructStupid()
    {
        $container = new Container();
        for ($i = 0; $i < 1000; $i++) {
            $container->set("service$i", PropertyTestClass::class);
        }
    }

    /**
     * @Revs(1000)
     */
    public function benchConstructSmart()
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
    public function benchSequentialLookups($params)
    {
        $this->container->set('service', $params['serviceClass']);
        if (isset($params['otherDefinitions'])) {
            $this->container->setAll($params['otherDefinitions']);
        }
        for ($i = 0; $i < 1000; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            $this->container->get("service$i");
        }
    }

    /**
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookups($params)
    {
        $this->container->set('service', $params['serviceClass']);
        if (isset($params['otherDefinitions'])) {
            $this->container->setAll($params['otherDefinitions']);
        }
        for ($i = 0; $i < 1000; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            $this->container->get("service$index");
        }
    }

    /**
     * @ParamProviders({"provideDefinitions"})
     */
    public function benchRandomLookupsComposite($params)
    {
        $this->container->set('service', $params['serviceClass']);
        if (isset($params['otherDefinitions'])) {
            $this->container->setAll($params['otherDefinitions']);
        }
        for ($i = 0; $i < 1000; $i++) {
            // Do array lookup.
            $index = $this->indexes[$i];
            $this->composite->get("service$index");
        }
    }
}
