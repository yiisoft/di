<?php


namespace yii\di\traits;


use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;

trait RecursiveResolveTrait
{

    private function recursiveResolve(DependencyInterface $reference, ContainerInterface $container)
    {
        while($reference instanceof DependencyInterface) {
            $reference = $reference->resolve($container);
        }
        return $reference;
    }
}