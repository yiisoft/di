<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

interface StateResetterInterface
{
    public function setResetters(array $resetters): void;

    public function reset(): void;
}

