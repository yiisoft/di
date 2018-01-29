<?php

namespace yii\di\tests\code;

/**
 * TreeItem
 */
class TreeItem
{
    private $treeItem;


    /**
     * TreeItem constructor.
     * @param TreeItem $treeItem
     */
    public function __construct(TreeItem $treeItem)
    {
        $this->treeItem = $treeItem;
    }
}
