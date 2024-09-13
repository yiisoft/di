<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class EngineStorage
{
    private readonly array $engines;

    public function __construct(EngineInterface ...$engines)
    {
        $this->engines = $engines;
    }

    public function getEngines(): array
    {
        return $this->engines;
    }
}
