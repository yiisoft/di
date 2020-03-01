<?php

namespace Yiisoft\Di;

use Yiisoft\Yii\Debug\Collector\CollectorTrait;

final class CommonServiceCollector implements CommonServiceCollectorInterface
{
    use CollectorTrait;

    private array $items = [];

    public function getCollected(): array
    {
        return $this->items;
    }

    public function collect(
        string $service,
        string $class,
        string $method,
        ?array $arguments,
        $result,
        string $status,
        ?object $error,
        float $timeStart,
        float $timeEnd
    ): void {
        $this->items[] = [
            'service' => $service,
            'class' => $class,
            'method' => $method,
            'arguments' => $arguments,
            'result' => $result,
            'status' => $status,
            'error' => $error,
            'timeStart' => $timeStart,
            'timeEnd' => $timeEnd,
        ];
    }
}
