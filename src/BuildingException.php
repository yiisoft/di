<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

/**
 * It wraps all exceptions that don't implement `ContainerExceptionInterface` during the build process.
 * Also adds building context for more understanding.
 */
final class BuildingException extends Exception implements ContainerExceptionInterface
{
    /**
     * @param string $id ID of the definition or name of the class that wasn't found.
     * @param string[] $buildStack Stack of IDs of services requested definition or class that wasn't found.
     */
    public function __construct(
        string $id,
        Throwable $error,
        array $buildStack = [],
        Throwable $previous = null,
    ) {
        $message = sprintf(
            'Caught unhandled error "%s" while building "%s".',
            $error->getMessage() === '' ? $error::class : $error->getMessage(),
            implode('" -> "', $buildStack === [] ? [$id] : $buildStack)
        );

        parent::__construct($message, 0, $previous);
    }
}
