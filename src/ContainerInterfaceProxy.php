<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class ContainerInterfaceProxy implements ContainerProxyInterface
{
    const LOG_ARGUMENTS = 1 << 0;

    const LOG_RESULT = 1 << 1;

    const LOG_ERROR = 1 << 2;

    protected ContainerInterface $container;

    private int $logLevel = 0;

    private array $trackedServices = [];

    private bool $active = false;

    private ?EventDispatcherInterface $dispatcher = null;

    private ?CommonServiceCollectorInterface $commonCollector = null;

    private array $serviceProxy = [];

    private ?object $currentError = null;

    private ?string $proxyCachePath = null;

    private ProxyManager $proxyManager;

    public function __construct(
        bool $active,
        array $trackedServices,
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher = null,
        CommonServiceCollectorInterface $commonCollector = null,
        string $proxyCachePath = null,
        int $logLevel = 0
    ) {
        $this->active = $active;
        $this->trackedServices = $trackedServices;
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->commonCollector = $commonCollector;
        $this->proxyCachePath = $proxyCachePath;
        $this->logLevel = $logLevel;
        $this->proxyManager = new ProxyManager($this->proxyCachePath);
    }

    public function withTrackedServices(array $trackedServices): ContainerProxyInterface
    {
        $proxy = clone $this;
        $proxy->trackedServices = array_merge($this->trackedServices, $trackedServices);

        return $proxy;
    }

    public function isActive(): bool
    {
        return $this->active && ($this->commonCollector !== null || $this->dispatcher !==null) && $this->trackedServices !== [];
    }

    public function get($id, array $params = [])
    {
        $this->resetCurrentError();
        try {
            $instance = null;
            $timeStart = microtime(true);
            $instance = $this->getInstance($id, $params);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->log('get', [$id, $params], $instance, $timeStart);
        }

        if ($this->isTracked($id) && (($proxy = $this->getServiceProxyCache($id)) || ($proxy = $this->getServiceProxy($id, $instance)))) {
            $this->setServiceProxyCache($id, $proxy);
            return $proxy;
        }

        return $instance;
    }

    public function has($id): bool
    {
        $this->resetCurrentError();
        try {
            $result = null;
            $timeStart = microtime(true);
            $result = $this->container->has($id);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->log('has', [$id], $result, $timeStart);
            return $result;
        }
    }

    protected function getCurrentResultStatus(): string
    {
        return $this->currentError === null ? 'success' : 'failed';
    }

    protected function repeatError(object $error): void
    {
        $this->currentError = $error;
        $errorClass = get_class($error);
        throw new $errorClass($error->getMessage());
    }

    protected function resetCurrentError(): void
    {
        $this->currentError = null;
    }

    protected function log(string $method, array $arguments, $result, float $timeStart)
    {
        if ($this->commonCollector === null) {
            return;
        }

        if (!($this->logLevel & self::LOG_ARGUMENTS)) {
            $arguments = null;
        }
        if (!($this->logLevel & self::LOG_RESULT)) {
            $result = null;
        }
        $error = $this->currentError;
        if (!($this->logLevel & self::LOG_ERROR)) {
            $error = null;
        }

        if ($this->commonCollector !== null) {
            $this->logToCollector($method, $arguments, $result, $error, $timeStart);
        }

        if ($this->dispatcher !== null) {
            $this->logToEvent($method, $arguments, $result, $error, $timeStart);
        }
    }

    private function logToCollector(string $method, array $arguments, $result, ?object $error, float $timeStart): void
    {
        $this->commonCollector->collect(
            ContainerInterface::class,
            get_class($this->container),
            $method,
            $arguments,
            $result,
            $this->getCurrentResultStatus(),
            $error,
            $timeStart,
            microtime(true),
            );
    }

    private function logToEvent(string $method, array $arguments, $result, ?object $error, float $timeStart): void
    {
        $this->dispatcher->dispatch(new ProxyMethodCallEvent(
            ContainerInterface::class,
            get_class($this->container),
            $method,
            $arguments,
            $result,
            $this->getCurrentResultStatus(),
            $error,
            $timeStart,
            microtime(true),
            ));
    }

    private function isTracked(string $service): bool
    {
        return isset($this->trackedServices[$service]) || in_array($service, $this->trackedServices, true);
    }

    private function getServiceProxy(string $service, object $instance): ?object
    {
        if (!$this->isTracked($service)) {
            return null;
        }

        if (isset($this->trackedServices[$service]) && is_callable($this->trackedServices[$service])) {
            return $this->trackedServices[$service]($this->container);
        } elseif (isset($this->trackedServices[$service]) && is_array($this->trackedServices[$service])) {
            return $this->getServiceProxyFromArray($service, $instance);
        } elseif (interface_exists($service) && $this->commonCollector !== null) {
            return $this->getCommonServiceProxy($service, $instance);
        }

        return null;
    }

    private function getServiceProxyFromArray(string $service, object $instance): ?object
    {
        try {
            $params = $this->trackedServices[$service];
            $proxyClass = array_shift($params);
            foreach ($params as $index => $param) {
                $params[$index] = $this->container->get($param);
            }
            return new $proxyClass($instance, ...$params);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getCommonServiceProxy(string $service, object $instance): object
    {
        return $this->proxyManager->createObjectProxyFromInterface(
            $service,
            CommonServiceProxy::class,
            [$service, $instance, $this->commonCollector, $this->dispatcher, $this->logLevel]
        );
    }

    private function getInstance(string $id, array $params)
    {
        if ($params === []) {
            return $instance = $this->container->get($id);
        }

        return $instance = $this->container->get($id, $params);
    }

    private function getServiceProxyCache(string $service): ?object
    {
        return $this->serviceProxy[$service] ?? null;
    }

    private function setServiceProxyCache(string $service, object $instance): void
    {
        $this->serviceProxy[$service] = $instance;
    }
}
