<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use yii\di\Reference;
use yii\di\tests\code\EngineInterface;

/**
 * Tests for CallableReference
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
class CallableReferenceTest extends TestCase
{
    
    public function testGet()
    {
        $container = new \yii\di\Container();
        $callableRef = \yii\di\CallableReference::to(function($container)
            {return ($container instanceof \Psr\Container\ContainerInterface) ? 'valid':'invalid';}
        );
        $this->assertEquals('valid', $callableRef->get($container));
    }
    
}
