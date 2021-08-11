<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * EngineMarkOne
 */
class EngineStorage
{
    private array $engines;

    public function __construct(EngineInterface ...$engines)
    {
        $this->engines = $engines;
    }

    public function getEngines(): array
    {
        return $this->engines;
    }
}
