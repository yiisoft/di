<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Yiisoft\Factory\Factory;

final class ContainerBuilder
{
    private ContainerInterface $container;

    private ?ContainerProxyInterface $containerProxy = null;

    private array $decoratedServices = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setContainerProxy(ContainerProxyInterface $containerProxy): self
    {
        $this->containerProxy = $containerProxy;
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

    public function addDecoratedService(string $service, $decoration = null): self
    {
        if ($decoration === null) {
            $this->decoratedServices[] = $service;
        } else {
            $this->decoratedServices[$service] = $decoration;
        }
        return $this;
    }

    public function build()
    {
        if ($this->containerProxy !== null) {
            $this->containerProxy = $this->containerProxy->withDecoratedServices($this->decoratedServices);
        } elseif ($this->containerProxy === null && $this->container->has(ContainerProxyInterface::class)) {
            try {
                $containerProxy = $this->container->get(ContainerProxyInterface::class);
                if ($containerProxy) {
                    $this->containerProxy = $containerProxy->withDecoratedServices($this->decoratedServices);
                }
            } catch (ContainerExceptionInterface $e) {
                $this->containerProxy = null;
            }
        }

        return $this->getContainer();
    }

    private function getContainer(): ContainerInterface
    {
        return $this->hasActiveProxy() ? $this->containerProxy : $this->container;
    }

    private function hasActiveProxy(): bool
    {
        return $this->containerProxy !== null && $this->containerProxy->isActive();
    }
}
