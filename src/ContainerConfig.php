<?php

namespace Yiisoft\Di;

/**
 * Container configuration.
 */
final class ContainerConfig
{
    private array $providers = [];
    private array $tags = [];
    private bool $validate = true;
    private array $delegates = [];

    /**
     * @param array $providers Service providers to get definitions from.
     * @return self
     */
    public function withProviders(array $providers): self
    {
        $new = clone $this;
        $new->providers = $providers;
        return $new;
    }

    /**
     * @return array Service providers to get definitions from.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param array $tags Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     * @return self
     */
    public function withTags(array $tags): self
    {
        $new = clone $this;
        $new->tags = $tags;
        return $new;
    }

    /**
     * @return array Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param bool $validate Whether definitions should be validated immediately.
     * @return self
     */
    public function withValidate(bool $validate): self
    {
        $new = clone $this;
        $new->validate = $validate;
        return $new;
    }

    /**
     * @return bool Whether definitions should be validated immediately.
     */
    public function shouldValidate(): bool
    {
        return $this->validate;
    }

    /**
     * @param array $delegates Container delegates. Each delegate is a callable in format
     * "function (ContainerInterface $container): ContainerInterface". The container instance returned is used
     * in case a service can not be found in primary container.
     * @return self
     */
    public function withDelegates(array $delegates): self
    {
        $new = clone $this;
        $new->delegates = $delegates;
        return $new;
    }

    /**
     * @return array Container delegates. Each delegate is a callable in format
     * "function (ContainerInterface $container): ContainerInterface". The container instance returned is used
     * in case a service can not be found in primary container.
     */
    public function getDelegates(): array
    {
        return $this->delegates;
    }
}
