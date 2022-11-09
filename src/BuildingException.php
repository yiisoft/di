<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * `NotFoundException` is thrown when no definition or class was found in the container for a given ID.
 */
final class BuildingException extends Exception implements ContainerExceptionInterface
{
    /**
     * @param string $id ID of the definition or name of the class that was not found.
     * @param string[] $buildStack Stack of IDs of services requested definition or class that was not found.
     */
    public function __construct(
        string $id,
        string $error,
        array $buildStack = [],
        Throwable $previous = null,
    ) {
        $message = sprintf('An error "%s" occurred while building "%s"', $error, $id);

        if ($buildStack !== []) {
            $message .= '"' . implode('" -> "', $buildStack) . '"';
        }

        $message .= '.';

        parent::__construct($message, 0, $previous);
    }
}
