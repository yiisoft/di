<?php

namespace Yiisoft\Di\Tests\Benchmark;

use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\GearBox;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Factory\Definitions\Reference;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @Groups({"has"})
 * @BeforeMethods({"before"})
 */
class ContainerMethodHasBench
{
    /** @var Container */
    private $container;

    /**
     * Load the bulk of the definitions.
     */
    public function before(): void
    {
        $definitions = [];
        $definitions2 = [];
        for ($i = 0; $i < 200; $i++) {
            $this->indexes[] = $i;
            $definitions["service$i"] = Reference::to('service');
            $definitions2["second$i"] = Reference::to('service');
            $definitions3["third$i"] = Reference::to('service');
        }

        $this->container = new Container($definitions);
        $this->container->set('service', PropertyTestClass::class);
    }

    public function benchPredefinedExisting(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->container->has("service$i");
        }
    }

    public function benchUndefinedExisting(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->container->has(GearBox::class);
        }
    }

    public function benchUndefinedNonexistent(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->container->has('NonexistentNamespace\NonexistentClass');
        }
    }
}
