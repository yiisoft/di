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
 * Description of ReferencingArrayTest
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development <andreas.prucha@gmail.com>
 */
class ReferencingArrayTest extends TestCase
{
    
    public function testGet()
    {
        $container = new \yii\di\Container();
        $container->set('m1', new code\EngineMarkOne());
        $container->set('m2', new code\EngineMarkTwo());
        $refArray = \yii\di\ReferencingArray::items([
            'markOne' => Reference::to('m1'),
            'markTwo' => Reference::to('m2'),
            'devil' => 666]);
        $result = $refArray->get($container);
        $this->assertIsArray($result);
        $this->assertInstanceOf(code\EngineMarkOne::class, $result['markOne']);
        $this->assertInstanceOf(code\EngineMarkTwo::class, $result['markTwo']);
        $this->assertEquals(666, $result['devil']);
    }
    
}
