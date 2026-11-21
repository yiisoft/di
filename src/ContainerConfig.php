<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Closure;

/**
 * Container configuration.
 */
final class ContainerConfig implements ContainerConfigInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $definitions = [];

    /**
     * @var mixed[]
     */
    private array $providers = [];

    /**
     * @var array<string, string[]>
     */
    private array $tags = [];

    private bool $validate = true;

    /**
     * @var Closure[]
     */
    private array $delegates = [];
    private bool $useStrictMode = false;

    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> $definitions Definitions to put into container.
     */
    public function withDefinitions(array $definitions): self
    {
        $new = clone $this;
        $new->definitions = $definitions;
        return $new;
    }

    /**
     * @return array<string, mixed> Definitions to put into container.
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @param mixed[] $providers Service providers to get definitions from.
     */
    public function withProviders(array $providers): self
    {
        $new = clone $this;
        $new->providers = $providers;
        return $new;
    }

    /**
     * @return mixed[] Service providers to get definitions from.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param array<string, string[]> $tags Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     */
    public function withTags(array $tags): self
    {
        $new = clone $this;
        $new->tags = $tags;
        return $new;
    }

    /**
     * @return array<string, string[]> Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param bool $validate Whether definitions should be validated immediately.
     */
    public function withValidate(bool $validate = true): self
    {
        $new = clone $this;
        $new->validate = $validate;
        return $new;
    }

    public function shouldValidate(): bool
    {
        return $this->validate;
    }

    /**
     * @param Closure[] $delegates Container delegates. Each delegate is a callable in format
     * `function (ContainerInterface $container): ContainerInterface`. The container instance returned is used
     * in case a service can't be found in primary container.
     */
    public function withDelegates(array $delegates): self
    {
        $new = clone $this;
        $new->delegates = $delegates;
        return $new;
    }

    /**
     * @return Closure[] Container delegates. Each delegate is a callable in format
     * `function (ContainerInterface $container): ContainerInterface`. The container instance returned is used
     * in case a service can't be found in primary container.
     */
    public function getDelegates(): array
    {
        return $this->delegates;
    }

    /**
     * @param bool $useStrictMode If the automatic addition of definition when class exists and can be resolved
     * is disabled.
     */
    public function withStrictMode(bool $useStrictMode = true): self
    {
        $new = clone $this;
        $new->useStrictMode = $useStrictMode;
        return $new;
    }

    public function useStrictMode(): bool
    {
        return $this->useStrictMode;
    }
}
