<?php

namespace Yiisoft\Di\Tests\Benchmark;

use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\GearBox;
use Yiisoft\Di\Tests\Support\PropertyTestClass;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @BeforeMethods({"before"})
 */
class ContainerMethodHasBench
{
    /** @var Container */
    private $container;

    public function before(): void
    {
        $this->container = new Container();
        $this->container->set(PropertyTestClass::class, PropertyTestClass::class);
    }

    public function benchPredefinedExisting(): void
    {
        $this->container->has(PropertyTestClass::class);
    }

    public function benchUndefinedExisting(): void
    {
        $this->container->has(GearBox::class);
    }

    public function benchUndefinedNonexistent(): void
    {
        $this->container->has('NonexistentNamespace\NonexistentClass');
    }
}
