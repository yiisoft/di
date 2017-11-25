<?php
namespace yii\di;


use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException is thrown when no entry was found in the container.
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{

}