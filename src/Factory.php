<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Di\Helpers\DefinitionNormalizer;
use Yiisoft\Di\Helpers\DefinitionParser;
use Yiisoft\Di\Helpers\TagHelper;

use function array_key_exists;
use function array_keys;
use function get_class;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;

final class Factory
{
    private ContainerInterface $container;

    private DefinitionStorage $definitions;

    private array $building;


    public function __construct(ContainerInterface $container, DefinitionStorage $definitions = null)
    {
        $this->container = $container;

        if($definitions === null) {
            $this->definitions = new DefinitionStorage();
            $this->definitions->setDelegateContainer($container);
        } else {
            $this->definitions = $definitions;
        }
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param string $id The interface or an alias name that was previously registered.
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     *
     * @return mixed|object New built instance of the specified class.
     *
     * @internal
     */
    public function create(string $id)
    {
        if (isset($this->building[$id])) {
            if ($id === ContainerInterface::class) {
                return $this->container;
            }
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s.',
                $id,
                implode(', ', array_keys($this->building))
            ));
        }

        $this->building[$id] = 1;
        try {
            /** @var mixed $object */
            $object = $this->buildInternal($id);
        } finally {
            unset($this->building[$id]);
        }

        return $object;
    }

    public function withDefinitions(array $definitions)
    {
        $new = clone $this;
        $new->definitions = new DefinitionStorage($definitions);
        $new->definitions->setDelegateContainer($this->container);

        return $new;
    }

    /**
     * @param string $id
     *
     * @throws InvalidConfigException
     * @throws NotFoundException
     *
     * @return mixed|object
     */
    private function buildInternal(string $id)
    {
        if ($this->definitions->has($id)) {
            $definition = DefinitionNormalizer::normalize($this->definitions->get($id), $id);

            return $definition->resolve($this->container->get(ContainerInterface::class));
        }

        throw new NotFoundException($id, $this->definitions->getBuildStack());
    }
}
