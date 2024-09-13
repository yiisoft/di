<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;

use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

/**
 * Test the Yiisoft PSR-11 Container.
 */
final class YiisoftPsrContainerTest extends PsrContainerTestAbstract
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        $config = ContainerConfig::create()
            ->withDefinitions($definitions);
        return new Container($config);
    }
}
