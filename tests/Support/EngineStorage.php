<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * EngineMarkOne
 */
class EngineStorage
{
    private EngineInterface $engines;

    public function __construct(EngineInterface $engines)
    {
        $this->engines = $engines;
    }

    public function getEngines(): EngineInterface
    {
        return $this->engines;
    }
}
