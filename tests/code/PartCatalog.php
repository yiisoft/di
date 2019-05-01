<?php

namespace yii\di\tests\code;

class PartCatalog
{
    public $engines = [];
    public $created = null;
    
    public function __construct(array $engines)
    {
        $this->engines = $engines;
    }
}
