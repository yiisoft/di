<?php


namespace yii\di\traits;

use Psr\Container\ContainerInterface;
use yii\di\contracts\Definition;

trait RecursiveResolveTrait
{
    private function recursiveResolve(Definition $reference, ContainerInterface $container)
    {
        while ($reference instanceof Definition) {
            $reference = $reference->resolve($container);
        }
        return $reference;
    }
}
