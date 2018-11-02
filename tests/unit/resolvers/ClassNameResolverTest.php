<?php

namespace yii\di\tests\unit;

use PHPUnit\Framework\TestCase;
use yii\di\Container;
use yii\di\contracts\DependencyInterface;
use yii\di\dependencies\ClassDependency;
use yii\di\exceptions\NotFoundException;
use yii\di\resolvers\ClassNameResolver;
use yii\di\tests\support\Car;
use yii\di\tests\support\GearBox;
use yii\di\tests\support\NullableConcreteDependency;
use yii\di\tests\support\NullableInterfaceDependency;
use yii\di\tests\support\OptionalConcreteDependency;
use yii\di\tests\support\OptionalInterfaceDependency;

class ClassNameResolverTest extends TestCase
{

    public function testResolveConstructor()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(\DateTime::class);

        $this->assertCount(2, $dependencies);
        // Since reflection for built in classes does not get default values.
        $this->assertEquals(null, $dependencies[0]->resolve($container));
        $this->assertEquals(null, $dependencies[1]->resolve($container));
    }

    public function testResolveCarConstructor()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(Car::class);

        $this->assertCount(1, $dependencies);
        $this->assertInstanceOf(ClassDependency::class, $dependencies[0]);
        $this->expectException(NotFoundException::class);
        $dependencies[0]->resolve($container);
    }

    public function testResolveGearBoxConstructor()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(GearBox::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(5, $dependencies[0]->resolve($container));
    }

    public function testOptionalInterfaceDependency()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(OptionalInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies[0]->resolve($container));
    }
    public function testNullableInterfaceDependency()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(NullableInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies[0]->resolve($container));
    }

    public function testOptionalConcreteDependency()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(OptionalConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies[0]->resolve($container));
    }
    public function testNullableConcreteDependency()
    {
        $resolver = new ClassNameResolver();
        $container = new Container();
        /** @var DependencyInterface[] $dependencies */
        $dependencies = $resolver->resolveConstructor(NullableConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies[0]->resolve($container));
    }
}