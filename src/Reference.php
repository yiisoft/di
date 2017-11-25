<?php

namespace yii\di;

/**
 * Reference points to another container definition by its ID
 */
class Reference
{
    /**
     * @var string the component ID, class name, interface name or alias name
     */
    public $id;


    /**
     * Constructor.
     * @param string $id the component ID
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Restores class state after using `var_export()`.
     *
     * @param array $state
     * @return Reference
     * @throws \yii\di\InvalidConfigException when $state property does not contain `id` parameter
     * @see var_export()
     */
    public static function __set_state($state)
    {
        if (!isset($state['id'])) {
            throw new InvalidConfigException('Failed to instantiate class "Instance". Required parameter "id" is missing');
        }

        return new self($state['id']);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
