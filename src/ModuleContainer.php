<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Definition\ArrayDefinition;
use Yiisoft\Factory\Definition\Normalizer;
use Yiisoft\Factory\Exception\NotFoundException;

final class ModuleContainer implements ContainerInterface
{
    private array $building = [];
    private array $resolved = [];
    private string $namespace;
    private array $definitions;
    private array $submoduleContainers = [];
    private array $submoduleDefinitions;

    public function __construct(
        string $namespace,
        array $definitions,
        array $submoduleDefinitions = []
    ) {
        $this->namespace = $namespace;
        $this->definitions = $definitions;
        $this->submoduleDefinitions = $submoduleDefinitions;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        return $this->resolved[$id] ?? $this->resolve($id);
    }

    public function has($id): bool
    {
        return $id === ContainerInterface::class
            || isset($this->resolved[$id])
            || isset($this->definitions[$id])
            || strpos($id, $this->namespace) === 0;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    private function resolve(string $id)
    {
        if ($id === ContainerInterface::class) {
            return $this;
        }

        if (isset($this->building[$id])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Circular reference to "%s" detected while building: %s.',
                    $id,
                    implode(',', array_keys($this->building))
                )
            );
        }
        $this->building[$id] = true;

        try {
            if (isset($this->definitions[$id])) {
                $this->resolved[$id] = $this->build($this->definitions[$id]);
            } elseif (class_exists($id)) {
                $container = $this->getSubmoduleContainer($id);
                if ($container === null) {
                    if ($this->getNamespaceMatch($id, $this->namespace) < count(explode('\\', $this->namespace))) {
                        throw new NotFoundException($id);
                    }

                    $this->resolved[$id] = $this->build($id);
                } else {
                    $this->resolved[$id] = $container->get($id);
                }
            }
        } finally {
            unset($this->building[$id]);
        }

        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        throw new NotFoundException($id);
    }

    private function build($definition)
    {
        if (is_string($definition)) {
            $definition = new ArrayDefinition([ArrayDefinition::CLASS_NAME => $definition], false);
        } else {
            $definition = Normalizer::normalize($definition);
        }

        return $definition->resolve($this);
    }

    private function getSubmoduleContainer(string $id): ?self
    {
        $namespaceChosen = null;
        $length = $this->getNamespaceMatch($id, $this->namespace);

        foreach ($this->submoduleDefinitions as $namespace => $definitions) {
            $match = $this->getNamespaceMatch($id, $namespace);
            if ($match > $length) {
                $length = $match;
                $namespaceChosen = $namespace;
            }
        }

        if ($namespaceChosen !== null) {
            if (!isset($this->submoduleContainers[$namespaceChosen])) {
                $definitions = $this->submoduleDefinitions[$namespaceChosen];
                $submodules = $definitions[ModuleRootContainer::KEY_SUBMODULES] ?? [];
                unset($definitions[ModuleRootContainer::KEY_SUBMODULES]);

                $this->submoduleContainers[$namespaceChosen] = new self(
                    $namespaceChosen,
                    $definitions,
                    $submodules
                );
            }

            return $this->submoduleContainers[$namespaceChosen];
        }

        return null;
    }

    private function getNamespaceMatch(string $className, string $namespace): int
    {
        $idNamespace = explode('\\', trim($className, '\\'));
        array_pop($idNamespace); // remove class name

        $namespaceDivided = explode('\\', $namespace);

        $result = 0;
        foreach ($namespaceDivided as $i => $part) {
            if ($idNamespace[$i] === $part) {
                $result++;
            } else {
                return $result;
            }
        }

        return $result;
    }
}
