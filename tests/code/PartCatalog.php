<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
