<?php

namespace Yiisoft\Di;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

interface CommonServiceCollectorInterface extends CollectorInterface
{
    public function collect(
        string $service,
        string $class,
        string $method,
        array $arguments,
        $result,
        string $status,
        ?object $error,
        float $timeStart,
        float $timeEnd
    ): void;
}
