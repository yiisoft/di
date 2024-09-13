<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * `NotFoundException` is thrown when no definition or class was found in the container for a given ID.
 */
final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * @param string $id ID of the definition or name of the class that was not found.
     * @param string[] $buildStack Stack of IDs of services requested definition or class that was not found.
     */
    public function __construct(
        private readonly string $id,
        private array $buildStack = [],
        ?Throwable $previous = null,
    ) {
        if (empty($this->buildStack)) {
            $message = sprintf('No definition or class found or resolvable for "%s".', $id);
        } elseif ($this->buildStack === [$id]) {
            $message = sprintf('No definition or class found or resolvable for "%s" while building it.', $id);
        } else {
            $message = sprintf(
                'No definition or class found or resolvable for "%s" while building "%s".',
                end($this->buildStack),
                implode('" -> "', $buildStack),
            );
        }

        parent::__construct($message, previous: $previous);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getBuildStack(): array
    {
        return $this->buildStack;
    }
}
