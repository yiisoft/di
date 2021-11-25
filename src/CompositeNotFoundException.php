<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * CompositeNotFoundException is thrown when no definition or class was found in the composite container for a given ID.
 * It contains all exceptions thrown by containers registered in the composite container.
 */
final class CompositeNotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * @param array $exceptions Exceptions of containers in [throwable, container] format.
     */
    public function __construct(array $exceptions)
    {
        $message = '';

        foreach ($exceptions as $i => [$exception, $container]) {
            $containerClass = get_class($container);
            $containerId = spl_object_id($container);
            $number = (int)$i + 1;

            $message .= "\n    $number. Container $containerClass #$containerId: {$exception->getMessage()}";
        }

        parent::__construct(sprintf('No definition or class found or resolvable in composite container:%s', $message));
    }
}
