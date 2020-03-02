<?php

namespace Yiisoft\Di;

use Psr\EventDispatcher\EventDispatcherInterface;

class CommonMethodProxy extends CommonServiceProxy
{
    private string $method;

    private $callback;

    public function __construct(
        string $service,
        object $instance,
        string $method,
        callable $callback,
        CommonServiceCollectorInterface $collector = null,
        EventDispatcherInterface $dispatcher = null,
        int $logLevel = 0
    ) {
        $this->method = $method;
        $this->callback = $callback;
        parent::__construct($service, $instance, $collector, $dispatcher, $logLevel);
    }

    protected function executeMethodProxy(string $method, array $arguments, $result, float $timeStart)
    {
        try {
            if ($method === $this->method) {
                $callback = $this->callback;
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
            $this->method,
            $this->callback,
            $this->getCollector(),
            $this->getDispatcher(),
            $this->getLogLevel()
        );
    }
}
