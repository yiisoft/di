<?php

declare(strict_types=1);

namespace Yiisoft\Di;

/**
 * Container configuration.
 */
final class ContainerConfig implements ContainerConfigInterface
{
    private array $definitions;
    private array $providers;
    private array $tags;
    private bool $validate;
    private array $delegates;

    private function __construct(
        array $definitions,
        array $providers,
        array $tags,
        bool $validate,
        array $delegates
    ) {
        $this->definitions = $definitions;
        $this->providers = $providers;
        $this->tags = $tags;
        $this->validate = $validate;
        $this->delegates = $delegates;
    }

    public static function create(
        array $definitions = [],
        array $providers = [],
        array $tags = [],
        bool $validate = true,
        array $delegates = []
    ): self {
        return new self($definitions, $providers, $tags, $validate, $delegates);
    }

    /**
     * @param array $definitions Definitions to put into container.
     *
     * @return self
     */
    public function withDefinitions(array $definitions): self
    {
        $new = clone $this;
        $new->definitions = $definitions;
        return $new;
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @param array $providers Service providers to get definitions from.
     *
     * @return self
     */
    public function withProviders(array $providers): self
    {
        $new = clone $this;
        $new->providers = $providers;
        return $new;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param array $tags Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     *
     * @return self
     */
    public function withTags(array $tags): self
    {
        $new = clone $this;
        $new->tags = $tags;
        return $new;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param bool $validate Whether definitions should be validated immediately.
     *
     * @return self
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
     * @param array $delegates Container delegates. Each delegate is a callable in format
     * "function (ContainerInterface $container): ContainerInterface". The container instance returned is used
     * in case a service can not be found in primary container.
     *
     * @return self
     */
    public function withDelegates(array $delegates): self
    {
        $new = clone $this;
        $new->delegates = $delegates;
        return $new;
    }

    public function getDelegates(): array
    {
        return $this->delegates;
    }
}
