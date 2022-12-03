<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Tests\Support\EngineMarkOne;

class LazyServiceContainerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(LazyLoadingValueHolderFactory::class)) {
            $this->markTestSkipped('You should install `friendsofphp/proxy-manager-lts` if you want to use lazy services.');
        }
    }

    public function testIsTheSameObject(): void
    {
        $class = EngineMarkOne::class;
        $number = 55;

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => $class,
                    'setNumber()' => [$number],
                    'lazy' => true,
                ],
            ]);
        $container = new Container($config);

        /* @var EngineMarkOne $object */
        $object = $container->get($class);

        self::assertInstanceOf(LazyLoadingInterface::class, $object);
        self::assertInstanceOf(EngineMarkOne::class, $object);
        self::assertFalse($object->isProxyInitialized());
        self::assertEquals($number, $object->getNumber());
        self::assertTrue($object->isProxyInitialized());

        /* @var EngineMarkOne $object */
        $object = $container->get($class);

        self::assertInstanceOf(LazyLoadingInterface::class, $object);
        self::assertInstanceOf(EngineMarkOne::class, $object);
        self::assertTrue($object->isProxyInitialized());
    }

    /**
     * @dataProvider lazyDefinitionDataProvider
     */
    public function testLazy(array $definitions, string $id): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions($definitions);
        $container = new Container($config);

        $object = $container->get($id);

        self::assertInstanceOf(LazyLoadingInterface::class, $object);
    }

    public function lazyDefinitionDataProvider(): array
    {
        return [
            'class as key' => [
                [EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'lazy' => true,
                ]],
                EngineMarkOne::class,
            ],
            'alias as key' => [
                ['mark_one' => [
                    'class' => EngineMarkOne::class,
                    'lazy' => true,
                ]],
                'mark_one',
            ],
            'dedicated array definition' => [
                [EngineMarkOne::class => [
                    'definition' => ['class' => EngineMarkOne::class],
                    'lazy' => true,
                ]],
                EngineMarkOne::class,
            ],
            'dedicated callback definition' => [
                [EngineMarkOne::class => [
                    'definition' => fn () => new EngineMarkOne(),
                    'lazy' => true,
                ]],
                EngineMarkOne::class,
            ],
        ];
    }
}
