<?php

namespace Yiisoft\Di;

final class ProxyMethodCallEvent
{
    public function __construct(
        string $service,
        string $class,
        string $method,
        array $arguments,
        $result,
        string $status,
        ?object $error,
        float $timeStart,
        float $timeEnd
    ) {
        $this->service = $service;
        $this->class = $class;
        $this->methodName = $method;
        $this->arguments = $arguments;
        $this->result = $result;
        $this->status = $status;
        $this->error = $error;
        $this->timeStart = $timeStart;
        $this->timeEnd = $timeEnd;
    }

    public string $service;

    public string $class;

    public string $methodName;

    public array $arguments = [];

    public $result = null;

    public string $status;

    public ?object $error = null;

    public float $timeStart;

    public float $timeEnd;
}
