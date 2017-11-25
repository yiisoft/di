<?php
namespace yii\di;

use Psr\Container\ContainerExceptionInterface;

/**
 * CircularReferenceException is thrown when DI configuration
 * contains self-references of any level and thus could not
 * be resolved.
 */
class CircularReferenceException extends \Exception implements ContainerExceptionInterface
{
    
}