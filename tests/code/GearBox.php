<?php

namespace yii\di\tests\code;

use yii\di\Initable;

/**
 * A gear box.
 */
class GearBox implements Initable
{
    private $maxGear;

    /**
     * @var bool
     */
    private $inited = false;

    public function __construct(int $maxGear = null)
    {
        $this->maxGear = $maxGear ?: 5;
    }

    public function init()
    {
        $this->inited = true;
    }

    /**
     * @return bool
     */
    public function getInited(): bool
    {
        return $this->inited;
    }
}
