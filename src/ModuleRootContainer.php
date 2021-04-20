<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Factory\Exception\NotFoundException;

final class ModuleRootContainer implements ContainerInterface
{
    private const KEY_DEFINITIONS = '#definitions';
    private const KEY_CONTAINER = '#container';
    private const KEY_SUBMODULE_NAMES = '#submoduleNames';
    public const KEY_SUBMODULES = '#submodules';

    private array $building = [];
    private array $definitionsDefault = [];
    private array $definitions = [];
    private array $definitionsPlain = [];

    /**
     * @param array $definitions
     * definition structure:
     * namespace => [
     *     dependencies => [namespace1, namespace2, namespace3]
     *     definitions => [classname => definition, ...]
     * ]
     */
    public function __construct(array $definitions)
    {
        $definitions = $this->prepareDefinitions($definitions);
        foreach ($definitions as $namespace => $moduleConfig) {
            $this->buildDefinitions($namespace, $definitions);
        }
    }

    private function setDefinitions(string $namespace, array $definitions): void
    {
        $definitionBag = &$this->definitions;
        foreach (explode('\\', trim($namespace, '\\')) as $part) {
            if (!isset($definitionBag[$part])) {
                $definitionBag[$part] = [];
            }

            $definitionBag = &$definitionBag[$part];
        }

        $definitionBag[self::KEY_DEFINITIONS] = $definitions;
        $definitionBag[self::KEY_CONTAINER] = null;
        $this->definitionsPlain[$namespace] = &$definitionBag[self::KEY_DEFINITIONS];
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function get($id)
    {
        return $this->resolved[$id] ?? $this->resolve($id);
    }

    private function getModuleContainer(string $id): ?ContainerInterface
    {
        $resultBag = null;
        $resultNamespace = $tempNamespace = [];
        $definitionBag = &$this->definitions;

        $namespace = explode('\\', trim($id, '\\'));
        array_pop($namespace); // remove class name

        foreach ($namespace as $part) {
            $tempNamespace[] = $part;
            if (isset($definitionBag[$part][self::KEY_DEFINITIONS])) {
                $resultBag = &$definitionBag[$part];
                $resultNamespace = $tempNamespace;
            }

            if (isset($definitionBag[$part])) {
                $definitionBag = &$definitionBag[$part];
            } else {
                break;
            }
        }

        if ($resultBag === null) {
            return null;
        }

        if ($resultBag[self::KEY_CONTAINER] === null) {
            $submodules = $resultBag[self::KEY_DEFINITIONS][self::KEY_SUBMODULE_NAMES] ?? [];
            unset($resultBag[self::KEY_DEFINITIONS][self::KEY_SUBMODULE_NAMES]);

            $submoduleDefinitions = [];
            foreach ($submodules as $submodule) {
                $submoduleDefinitions[$submodule] = $this->buildSubmoduleTree($submodule);
            }
            $resultBag[self::KEY_CONTAINER] = new ModuleContainer(
                implode('\\', $resultNamespace),
                $resultBag[self::KEY_DEFINITIONS],
                $submoduleDefinitions
            );
        }

        return $resultBag[self::KEY_CONTAINER];
    }

    private function getDefinitionDefaultContainer(string $id): ?ContainerInterface
    {
        if (isset($this->definitionsDefault[$id])) {
            return $this->getModuleContainer($this->definitionsDefault[$id] . '\\Dummy');
        }

        return null;
    }

    private function prepareDefinitions(array $definitions): array
    {
        $result = [];
        foreach ($definitions as $namespace => $moduleConfig) {
            $result[trim($namespace, " \t\n\r\0\x0B\\")] = $moduleConfig;
        }

        return $result;
    }

    private function getNamespaceMatch(string $id, string $namespace): int
    {
        $idNamespace = explode('\\', trim($id, '\\'));
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

    private function setDefaultDefinitions(string $namespace, array $definitions): void
    {
        foreach ($definitions as $id => $definition) {
            if (isset($this->definitionsDefault[$id])) {
                if (!class_exists($id) && !interface_exists($id)) {
                    $message = "Container definition id conflict: id '$id' exists "
                        . "in modules '$namespace' and '{$this->definitionsDefault[$id]}'";

                    throw new RuntimeException($message);
                }

                $matchCurrent = $this->getNamespaceMatch($id, $this->definitionsDefault[$id]);
                $matchNew = $this->getNamespaceMatch($id, $namespace);
                if ($matchNew !== 0 && $matchNew < $matchCurrent) {
                    $this->definitionsDefault[$id] = $namespace;
                }
            } else {
                $this->definitionsDefault[$id] = $namespace;
            }
        }
    }

    /**
     * Filters out current module definitions and remains only 3rd-party
     *
     * @param string $namespace
     * @param array $module
     *
     * @return array
     */
    private function getDependencyDefinitions(string $namespace, array $module): array
    {
        return array_filter(
            $module['definitions'] ?? [],
            fn(string $id) => $this->getNamespaceMatch($id, $namespace) === 0,
            ARRAY_FILTER_USE_KEY
        );
    }

    private function buildDefinitions(string $namespace, array $definitions): array
    {
        if (isset($this->definitionsPlain[$namespace])) {
            return $this->definitionsPlain[$namespace];
        }

        if (isset($this->building[$namespace])) {
            throw new RuntimeException('Circular module dependency');
        }

        $this->building[$namespace] = true;
        $moduleConfig = $definitions[$namespace];

        $this->setDefaultDefinitions($namespace, $moduleConfig['definitions'] ?? []);
        $definitionParts = [$moduleConfig['definitions'] ?? []];
        foreach ($moduleConfig['dependencies'] ?? [] as $dependencyNamespace) {
            if (!isset($definitions[$dependencyNamespace])) {
                throw new InvalidArgumentException(
                    "Dependency '$dependencyNamespace' of module '$namespace' is not defined"
                );
            }

            if (strpos($dependencyNamespace, $namespace) === 0) {
                // Dependency is a submodule of the current module
                $definitionParts[0][self::KEY_SUBMODULE_NAMES][] = $dependencyNamespace;
            } elseif (strpos($namespace, $dependencyNamespace) === 0) {
                // Dependency is a parent of the current module
                $parentDefinitions = $this->buildDefinitions($dependencyNamespace, $definitions);

                $definitionParts[] = $this->getDependencyDefinitions($dependencyNamespace, $parentDefinitions);
                foreach ($parentDefinitions[self::KEY_SUBMODULE_NAMES] as $parentSubmodule) {
                    if (strpos($parentSubmodule, $dependencyNamespace) !== 0) {
                        $definitionParts[0][self::KEY_SUBMODULE_NAMES][] = $parentSubmodule;
                    }
                }
            } else {
                // 3rd-party dependency
                $dependencyDefinitions = $this->buildDefinitions($dependencyNamespace, $definitions);

                $definitionParts[] = $dependencyDefinitions;
                $definitionParts[0][self::KEY_SUBMODULE_NAMES][] = $dependencyNamespace;
            }
        }

        $moduleDefinitions = array_merge(...array_reverse($definitionParts));
        $this->setDefinitions($namespace, $moduleDefinitions);

        unset($this->building[$namespace]);

        return $moduleDefinitions;
    }

    private function resolve(string $id)
    {
        if (class_exists($id)) {
            $container = $this->getModuleContainer($id);
        } else {
            $container = $this->getDefinitionDefaultContainer($id);
        }

        if ($container === null) {
            throw new NotFoundException($id);
        }

        return $container->get($id);
    }

    private function buildSubmoduleTree($submodule): array
    {
        $definitions = &$this->definitionsPlain[$submodule];
        if (isset($definitions[self::KEY_SUBMODULE_NAMES])) {
            foreach ($definitions[self::KEY_SUBMODULE_NAMES] as $subSubmodule) {
                $definitions[self::KEY_SUBMODULES][$subSubmodule] = $this->buildSubmoduleTree($subSubmodule);
            }

            unset($definitions[self::KEY_SUBMODULE_NAMES]);
        }

        return $definitions;
    }

    public function has($id)
    {
        // TODO: Implement has() method.
    }
}
