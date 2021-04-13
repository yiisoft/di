<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\GearBox;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Factory\Definition\Reference;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @Groups({"has"})
 * @BeforeMethods({"before"})
 */
class ContainerMethodHasBench
{
    private const SERVICE_COUNT = 200;

    private Container $container;

    /**
     * Load the bulk of the definitions.
     */
    public function before(): void
    {
        $definitions = [];
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $definitions["service$i"] = Reference::to('service');
        }
        $definitions['service'] = PropertyTestClass::class;

        $this->container = new Container($definitions);
    }

    public function benchPredefinedExisting(): void
    {
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $this->container->has("service$i");
        }
    }

    public function benchUndefinedExisting(): void
    {
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $this->container->has(GearBox::class);
        }
    }

    public function benchUndefinedNonexistent(): void
    {
        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $this->container->has('NonexistentNamespace\NonexistentClass');
        }
    }
}
