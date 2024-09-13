<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Di\Command\DebugContainerCommand;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

final class DebugContainerCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $container = $this->createContainer();
        $config = $container->get(ConfigInterface::class);
        // trigger config build
        $config->get('params');

        $command = new DebugContainerCommand($container);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function createContainer(): ContainerInterface
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                LoggerInterface::class => NullLogger::class,
                ConfigInterface::class => [
                    'class' => Config::class,
                    '__construct()' => [
                        new ConfigPaths(__DIR__ . '/config'),
                    ],
                ],
            ]);
        return new Container($config);
    }
}
