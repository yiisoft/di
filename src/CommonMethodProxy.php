<?php

namespace Yiisoft\Di;

use Psr\EventDispatcher\EventDispatcherInterface;

class CommonMethodProxy extends CommonServiceProxy
{
    private array $methods;

    public function __construct(
        string $service,
        object $instance,
        array $methods,
        CommonServiceCollectorInterface $collector = null,
        EventDispatcherInterface $dispatcher = null,
        int $logLevel = 0
    ) {
        $this->methods = $methods;
        parent::__construct($service, $instance, $collector, $dispatcher, $logLevel);
    }

    protected function executeMethodProxy(string $method, array $arguments, $result, float $timeStart)
    {
        try {
            if (isset($this->methods[$method])) {
                $callback = $this->methods[$method];
                $result = $callback($result, ...$arguments);
            }
        } finally {
            $this->log($method, $arguments, $result, $timeStart);
            return $result;
        }
    }

    protected function getNewStaticInstance(object $instance): ObjectProxy
    {
        return new static(
            $this->getService(),
            $instance,
            $this->methods,
            $this->getCollector(),
            $this->getDispatcher(),
            $this->getLogLevel()
        );
    }
}
