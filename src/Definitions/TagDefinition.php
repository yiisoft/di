<?php

declare(strict_types=1);

namespace Yiisoft\Di\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Definitions\DefinitionInterface;

class TagDefinition implements DefinitionInterface
{
    private array $references;

    public function __construct(array $ids)
    {
        $this->references = $ids;
    }

    public function resolve(ContainerInterface $container)
    {
        $results = [];
        foreach ($this->references as $id) {
            $results[] = $container->get($id);
        }

        return $results;
    }

    public function addReferenceTo(string $id): void
    {
        $this->references[$id] = $id;
    }
}
