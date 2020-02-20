<?php

namespace Yiisoft\Di;

use SplObjectStorage;
use Yiisoft\Factory\Definitions\Reference;

class ReferenceStorage extends SPLObjectStorage
{
    /**
     * @param Reference $ref
     * @return string
     */
    public function getHash($ref)
    {
        return $ref->getId();
    }
}
