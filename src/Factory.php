<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use yii\di\exceptions\InvalidConfigException;

/**
 * Factory is similar to the Container but creates new object every time.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
class Factory extends AbstractContainer implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->getDefinition($id) == null && $this->parent !== null) {
            return $this->parent->get($id);
        }

        return $this->initObject($this->build($id));
    }

    /**
     * {@inheritdoc}
     */
    public function create($config, array $params = [])
    {
        if (is_string($config)) {
            $config = ['__class' => $config];
        }

        if (is_array($config) && isset($config['__class'])) {
            if (!empty($params)) {
                $config['__construct()'] = array_merge($config['__construct()'] ?? [], $params);
            }
            $class = $config['__class'];
            unset($config['__class']);

            return $this->initObject($this->build($class, $config));
        }

        if (is_callable($config, true)) {
            return $this->getInjector()->invoke($config, $params);
        }

        if (is_array($config)) {
            throw new InvalidConfigException('Object configuration array must contain a "__class" element.');
        }

        throw new InvalidConfigException('Unsupported configuration type: ' . gettype($config));
    }

    /**
     * {@inheritdoc}
     */
    public function ensure($reference, string $type = null)
    {
        if (is_array($reference)) {
            if (empty($reference['__class'])) {
                $class = $type;
            } else {
                $class = $reference['__class'];
                unset($reference['__class']);
            }

            $component = $this->initObject($this->build($class, $reference));
            if ($type === null || $component instanceof $type) {
                return $component;
            }

            throw new InvalidConfigException(sprintf(
                'Invalid data type: %s. %s is expected.',
                get_class($component),
                $type
            ));
        }

        if (empty($reference)) {
            throw new InvalidConfigException('The required component is not specified.');
        }

        if (is_string($reference)) {
            $reference = new Reference($reference);
        } elseif ($type === null || $reference instanceof $type) {
            return $reference;
        }

        if ($reference instanceof Reference) {
            try {
                $this->get($reference);
            } catch (\ReflectionException $e) {
                throw new InvalidConfigException("Failed instantiate component or class '{$reference->id}'.", 0, $e);
            }
            if ($type === null || $component instanceof $type) {
                return $component;
            }

            throw new InvalidConfigException(sprintf(
                "'%s' refers to a %s component. %s is expected.",
                $reference->id,
                get_class($component),
                $type
            ));
        }

        $valueType = is_object($reference) ? get_class($reference) : gettype($reference);
        throw new InvalidConfigException("Invalid data type: $valueType. $type is expected.");
    }
}
