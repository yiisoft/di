<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException is thrown when no definition or class was found in the container for a given ID.
 */
final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    private string $id;

    /**
     * @param string $id ID of the definition or name of the class that was not found.
     * @param array $buildStack Stack of IDs of services requested definition or class that was not found.
     */
    public function __construct(string $id, array $buildStack = [])
    {
        $this->id = $id;

        $message = $id;
        if ($buildStack !== []) {
            $buildStack = array_keys($buildStack);
            $last = end($buildStack);
            $message = sprintf('%s" while building %s', $last, '"' . implode('" -> "', $buildStack));
        }

        parent::__construct(sprintf('No definition or class found or resolvable for "%s".', $message));
    }

    public function getId(): string
    {
        return $this->id;
    }
}
