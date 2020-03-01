<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Yiisoft\Factory\Factory;

final class ContainerBuilder
{
    private ContainerInterface $container;

    private ?ContainerProxyInterface $proxyContainer = null;

    private array $trackedServices = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setProxyContainer(ContainerProxyInterface $proxyContainer): self
    {
        $this->proxyContainer = $proxyContainer;
        return $this;
    }

    public function registerDefinitions(array $definitions): self
    {
        if ($this->container instanceof Container || $this->container instanceof Factory) {
            $this->container->setMultiple($definitions);
            return $this;
        }

        throw new \RuntimeException('This method is for Yiisoft\Di\Container and Yiisoft\Factory\Factory only');
    }

    public function registerServiceProviders(array $providers): self
    {
        if ($this->container instanceof Container) {
            $this->container->registerServiceProviders($providers);
            return $this;
        }

        throw new \RuntimeException('This method is for Yiisoft\Di\Container only');
    }

    public function addTrackedService(string $service): self
    {
        $this->trackedServices[] = $service;
        return $this;
    }

    public function build()
    {
        if ($this->proxyContainer === null && $this->container->has(ContainerProxyInterface::class)) {
            try {
                $proxyContainer = $this->container->get(ContainerProxyInterface::class);
                $this->proxyContainer = $proxyContainer->withTrackedServices($this->trackedServices);
            } catch (ContainerExceptionInterface $e) {
                $this->proxyContainer = null;
            }
        }

        return $this->getContainer();
    }

    private function getContainer(): ContainerInterface
    {
        return $this->hasActiveProxy() ? $this->proxyContainer : $this->container;
    }

    private function hasActiveProxy(): bool
    {
        return $this->proxyContainer !== null && $this->proxyContainer->isActive();
    }
}
