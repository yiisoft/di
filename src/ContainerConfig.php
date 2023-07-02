<?php

declare(strict_types=1);

namespace Yiisoft\Di;

/**
 * Container configuration.
 */
final class ContainerConfig implements ContainerConfigInterface
{
    private iterable $definitions = [];
    private iterable $providers = [];
    private iterable $tags = [];
    private bool $validate = true;
    private iterable $delegates = [];
    private bool $useStrictMode = false;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param iterable $definitions Definitions to put into container.
     */
    public function withDefinitions(iterable $definitions): self
    {
        $new = clone $this;
        $new->definitions = $definitions;
        return $new;
    }

    public function getDefinitions(): iterable
    {
        return $this->definitions;
    }

    /**
     * @param iterable $providers Service providers to get definitions from.
     */
    public function withProviders(iterable $providers): self
    {
        $new = clone $this;
        $new->providers = $providers;
        return $new;
    }

    public function getProviders(): iterable
    {
        return $this->providers;
    }

    /**
     * @param iterable $tags Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     */
    public function withTags(iterable $tags): self
    {
        $new = clone $this;
        $new->tags = $tags;
        return $new;
    }

    public function getTags(): iterable
    {
        return $this->tags;
    }

    /**
     * @param bool $validate Whether definitions should be validated immediately.
     */
    public function withValidate(bool $validate): self
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
     * @param iterable $delegates Container delegates. Each delegate is a callable in format
     * `function (ContainerInterface $container): ContainerInterface`. The container instance returned is used
     * in case a service can not be found in primary container.
     */
    public function withDelegates(iterable $delegates): self
    {
        $new = clone $this;
        $new->delegates = $delegates;
        return $new;
    }

    public function getDelegates(): iterable
    {
        return $this->delegates;
    }

    /**
     * @param bool $useStrictMode If the automatic addition of definition when class exists and can be resolved
     * is disabled.
     */
    public function withStrictMode(bool $useStrictMode): self
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
