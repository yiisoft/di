<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * CarInterface defines car interface
 */
interface CarInterface
{
    public function getEngine(): EngineInterface;

    public function getEngineName(): string;
}
