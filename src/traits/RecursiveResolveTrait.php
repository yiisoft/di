<?php


namespace yii\di\traits;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DefinitionInterface;

trait RecursiveResolveTrait
{
    private function recursiveResolve(DefinitionInterface $reference, ContainerInterface $container)
    {
        while ($reference instanceof DefinitionInterface) {
            $reference = $reference->resolve($container);
        }
        return $reference;
    }
}
