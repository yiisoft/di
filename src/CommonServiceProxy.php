<?php

namespace Yiisoft\Di;

use Psr\EventDispatcher\EventDispatcherInterface;

class CommonServiceProxy extends ObjectProxy
{
    const LOG_ARGUMENTS = 1 << 0;

    const LOG_RESULT = 1 << 1;

    const LOG_ERROR = 1 << 2;

    private string $service;

    private $logLevel = 0;

    private ?CommonServiceCollectorInterface $collector = null;

    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct(
        string $service,
        object $instance,
        CommonServiceCollectorInterface $collector = null,
        EventDispatcherInterface $dispatcher = null,
        int $logLevel = 0
    )
    {
        $this->service = $service;
        $this->collector = $collector;
        $this->dispatcher = $dispatcher;
        $this->logLevel = $logLevel;
        parent::__construct($instance);
    }

    protected function executeMethodProxy(string $methodName, array $arguments, $result, float $timeStart)
    {
        $this->log($methodName, $arguments, $result, $timeStart);
        return $result;
    }

    protected function getNewStaticInstance(object $instance): ObjectProxy
    {
        return new static($this->service, $instance, $this->collector, $this->dispatcher, $this->logLevel);
    }

    protected function log(string $method, array $arguments, $result, float $timeStart): void
    {
        if (!($this->logLevel & self::LOG_ARGUMENTS)) {
            $arguments = null;
        }

        if (!($this->logLevel & self::LOG_RESULT)) {
            $result = null;
        }
        $error = $this->getCurrentError();
        if (!($this->logLevel & self::LOG_ERROR)) {
            $error = null;
        }

        if ($this->collector !== null) {
            $this->logToCollector($method, $arguments, $result, $error, $timeStart);
        }

        if ($this->dispatcher !== null) {
            $this->logToEvent($method, $arguments, $result, $error, $timeStart);
        }
    }

    protected function getService(): string
    {
        return $this->service;
    }

    protected function getCollector(): CommonServiceCollectorInterface
    {
        return $this->collector;
    }

    protected function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    protected function getLogLevel(): int
    {
        return $this->logLevel;
    }

    private function logToCollector(string $method, array $arguments, $result, ?object $error, float $timeStart): void
    {
        $this->collector->collect(
            $this->service,
            get_class($this->getInstance()),
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
                $this->service,
                get_class($this->getInstance()),
                $method,
                $arguments,
                $result,
                $this->getCurrentResultStatus(),
                $error,
                $timeStart,
                microtime(true),
                )
        );
    }
}
