<?php

declare(strict_types=1);

namespace Unit\Hook;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Hook\AfterBuiltHook;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;

final class AfterBuiltHookTest extends TestCase
{
    public function testDifferentObjects()
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => [
                    'class' => EngineMarkOne::class,
                    'afterBuilt' => AfterBuiltHook::unsetInstance()
                ],
            ]);
        $container = new Container($config);

        $this->assertNotSame(
            $container->get(EngineInterface::class),
            $container->get(EngineInterface::class),
        );
    }
}
