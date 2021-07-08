<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\CompositeContainer;

class ModuleContainerTest extends AbstractModuleContainerTest
{
    public function createModuleContainer(array $rootDefinitions, array $moduleDefinitions): array
    {
        $compositeContainer = new CompositeContainer();
        //$rootContainer = new Container($rootDefinitions, [],  $compositeContainer);
        $rootContainer = new Container($rootDefinitions);
        $compositeContainer->attach($rootContainer);
        $compositeContainer->attach(new Container($moduleDefinitions, [], $compositeContainer));

        return [$rootContainer, $compositeContainer];
    }
}
