<?php

namespace Yiisoft\Di;

abstract class ObjectProxy
{
    private object $instance;

    private ?object $currentError = null;

    public function __construct(object $instance)
    {
        $this->instance = $instance;
    }

    protected function call(string $methodName, array $arguments)
    {
        $this->resetCurrentError();
        try {
            $result = null;
            $timeStart = microtime(true);
            $result = $this->callInternal($methodName, $arguments);
        } catch (\Exception $e) {
            $this->repeatError($e);
        } finally {
            $result = $this->executeMethodProxy($methodName, $arguments, $result, $timeStart);
        }

        return $this->processResult($result);
    }

    abstract protected function executeMethodProxy(string $methodName, array $arguments, $result, float $timeStart);

    protected function getCurrentError(): ?object
    {
        return $this->currentError;
    }

    protected function getCurrentResultStatus(): string
    {
        return $this->currentError === null ? 'success' : 'failed';
    }

    protected function getNewStaticInstance(object $instance): self
    {
        return new static($instance);
    }

    protected function getInstance(): object
    {
        return $this->instance;
    }

    private function callInternal(string $methodName, array $arguments)
    {
        return $this->instance->$methodName(...$arguments);
    }

    private function processResult($result)
    {
        if (is_object($result) && get_class($result) === get_class($this->instance)) {
            $result = $this->getNewStaticInstance($result);
        }

        return $result;
    }

    private function repeatError(object $error): void
    {
        $this->currentError = $error;
        $errorClass = get_class($error);
        throw new $errorClass($error->getMessage());
    }

    private function resetCurrentError(): void
    {
        $this->currentError = null;
    }
}
