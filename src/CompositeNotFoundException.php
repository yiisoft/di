<?php

namespace Yiisoft\Di;

use Exception;
use Throwable;
use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * CompositeNotFoundException is thrown when no definition or class was found in the composite container for a given ID.
 * It contains all exceptions thrown by containers registered in the composite container.
 */
final class CompositeNotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * @param Throwable[] $exceptions Exceptions of parent containers.
     */
    public function __construct(array $exceptions)
    {
        $message = '';

        $number = 1;
        foreach ($exceptions as $exception) {
            if (!$exception instanceof Throwable) {
                $type = is_object($exception) ? get_class($exception) : gettype($exception);
                $message = sprintf('An array of \Throwable is expected, "%s" given.', $type);
                throw new InvalidArgumentException($message);
            }

            $message .= "\n    Container #$number: {$exception->getMessage()}";
            $number++;
        }

        parent::__construct(sprintf("No definition or class found or resolvable in composite container:%s", $message));
    }
}
