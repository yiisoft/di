<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

/**
 * This class a container that uses a specific context in a CompositeContextContainer
 * The intended use is to allow for custom configurations of (nested) modules.
 */
class ContextContainer implements ContainerInterface
{
    private CompositeContextContainer $container;

    /**
     * @var string The context that this container uses
     */
    private string $context;

    public function __construct(CompositeContextContainer $parent, string $context)
    {
        $this->container = $parent;
        $this->context = $context;
    }

    public function get($id)
    {
        return $this->container->getFromContext($id, $this->context);
    }

    public function has($id): bool
    {
        return $this->container->hasInContext($id, $this->context);
    }
}
