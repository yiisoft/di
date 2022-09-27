<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * `CompositeNotFoundException` is thrown when no definition or class was found in the composite container
 * for a given ID. It contains all exceptions thrown by containers registered in the composite container.
 */
final class CompositeNotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * @param array $exceptions Container exceptions in [throwable, container] format.
     *
     * @psalm-param list<array{\Throwable,ContainerInterface}> $exceptions
     */
    public function __construct(array $exceptions)
    {
        $message = '';

        foreach ($exceptions as $i => [$exception, $container]) {
            $containerClass = $container::class;
            $containerId = spl_object_id($container);
            $number = $i + 1;

            $message .= "\n    $number. Container $containerClass #$containerId: {$exception->getMessage()}";
        }

        parent::__construct(sprintf('No definition or class found or resolvable in composite container:%s', $message));
    }
}
