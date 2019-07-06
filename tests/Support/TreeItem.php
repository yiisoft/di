<?php
namespace Yiisoft\Di\Tests\Support;

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
