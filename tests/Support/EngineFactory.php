<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Exception;
use Psr\Container\ContainerInterface;

/**
 * EngineFactory
 */
class EngineFactory
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function createByName(?string $name = null): EngineInterface
    {
        if ($name === EngineMarkOne::NAME) {
            return $this->container->get(EngineMarkOne::class);
        }
        if ($name === EngineMarkTwo::NAME) {
            return $this->container->get(EngineMarkTwo::class);
        }

        throw new Exception('unknown engine name: ' . $name);
    }

    public static function createDefault(): EngineInterface
    {
        return new EngineMarkOne();
    }
}
