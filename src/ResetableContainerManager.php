<?php

namespace Yiisoft\Di;

class ResetableContainerManager
{
    /** @var ResetableContainerInterface[]  */
    private array $containers = [];

    public function add(ResetableContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    public function reset(): void
    {
        foreach ($this->containers as $container) {
            $container->reset();
        }
    }
}
