<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace yii\di\tests\code;

/**
 * Description of PartCatalog
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development <andreas.prucha@gmail.com>
 */
class PartCatalog
{
    public $engines = [];
    
    public function __construct(array $engines)
    {
        $this->engines = $engines;
    }
}
