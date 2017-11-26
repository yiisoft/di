<?php

namespace yii\di\tests;


use PHPUnit\Framework\TestCase;
use yii\di\ClassDefinition;
use yii\di\Container;
use yii\di\tests\code\ConstructorTestClass;
use yii\di\tests\code\EngineMarkOne;
use yii\di\tests\code\MethodTestClass;
use yii\di\tests\code\PropertyTestClass;

class ClassDefinitionTest extends TestCase
{
    public function testClassSimple()
    {
        $definition = new ClassDefinition(EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $definition(new Container()));
    }

    public function testClassConstructor()
    {
        $definition = new ClassDefinition(ConstructorTestClass::class);
        $definition->setArguments([42]);
        $object = $definition(new Container());
        $this->assertSame(42, $object->getParameter());
    }

    public function testClassProperties()
    {
        $definition = new ClassDefinition(PropertyTestClass::class);
        $definition->setProperty('property', 42);
        $object = $definition(new Container());
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods()
    {
        $definition = new ClassDefinition(MethodTestClass::class);
        $definition->call('setValue', [42]);
        $object = $definition(new Container());
        $this->assertSame(42, $object->getValue());
    }
}