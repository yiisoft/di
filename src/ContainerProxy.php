<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ContainerProxy extends ContainerInterfaceProxy
{
    public function __construct(
        bool $active,
        array $decoratedServices,
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher = null,
        CommonServiceCollectorInterface $commonCollector = null,
        string $proxyCachePath = null,
        int $logLevel = 0
    ) {
        $container = $container instanceof Container ? $container->withParentContainer($this) : $container;
        parent::__construct($active, $decoratedServices, $container, $dispatcher, $commonCollector, $proxyCachePath, $logLevel);
    }

    public function set(string $id, $definition): void
    {
        $this->checkNativeContainer();
        $this->resetCurrentError();
        try {
            $timeStart = microtime(true);
            $this->container->set($id, $definition);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->log('set', [$id, $definition], null, $timeStart);
        }
    }

    public function setMultiple(array $config): void
    {
        $this->checkNativeContainer();
        $this->resetCurrentError();
        try {
            $timeStart = microtime(true);
            $this->container->setMultiple($config);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->log('setMultiple', [$config], null, $timeStart);
        }
    }

    public function addProvider($providerDefinition): void
    {
        $this->checkNativeContainer();
        $this->resetCurrentError();
        try {
            $timeStart = microtime(true);
            $this->container->addProvider($providerDefinition);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->log('addProvider', [$providerDefinition], null, $timeStart);
        }
    }

    public function withParentContainer(ContainerInterface $container): ContainerInterface
    {
        $this->checkNativeContainer();
        $this->container = $this->container->withParentContainer($container);

        return $this;
    }

    private function checkNativeContainer(): void
    {
        if (!$this->container instanceof Container) {
            throw new \RuntimeException('This method is for Yiisoft\Di\Container only');
        }
    }
}
