<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

use function sprintf;

/**
 * It wraps exceptions that occur during container operations other than building services.
 */
final class ContainerException extends Exception implements ContainerExceptionInterface, FriendlyExceptionInterface
{
    public function __construct(
        private readonly string $id,
        Throwable $error,
        ?Throwable $previous = null,
    ) {
        $message = sprintf(
            'Caught unhandled error "%s" while checking if container has "%s".',
            $error->getMessage() === '' ? $error::class : $error->getMessage(),
            $id,
        );

        parent::__construct($message, 0, $previous);
    }

    public function getName(): string
    {
        return sprintf('Unable to check if container has "%s" ID.', $this->id);
    }

    public function getSolution(): ?string
    {
        $solution = <<<SOLUTION
            Ensure delegated containers handle "%1\$s" ID in `has()` without throwing unexpected errors.
            SOLUTION;

        return sprintf($solution, $this->id);
    }
}
