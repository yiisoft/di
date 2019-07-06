<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Dependency Injection</h1>
    <br>
</p>

The library is a [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container that is able to instantiate
and configure classes resolving dependencies.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/di/v/stable.png)](https://packagist.org/packages/yiisoft/di)
[![Total Downloads](https://poser.pugx.org/yiisoft/di/downloads.png)](https://packagist.org/packages/yiisoft/di)
[![Build Status](https://travis-ci.org/yiisoft/di.svg?branch=master)](https://travis-ci.org/yiisoft/di)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/di/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/di/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/di/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/di/?branch=master)

## Features

- [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible.
- Supports property injection, constructor injection and method injection.
- Detects circular references.
- Accepts array definitions so could be used with mergeable configs.

## Using container

Usage of DI container is fairly simple. First, you set object definitions into it and then
they're used either in the application directly or to resolve dependencies of other definitions.

Usually there is a single container in the whole application so it's often configured in either entry
script such as `index.php` or a configuration file:

```php
$container = new Container($config);
```

Config could be stored in a `.php` file returning array:

```php
return [
    EngineInterface::class => EngineMarkOne::class,
    'full_definition' => [
        '__class' => EngineMarkOne::class,
        '__construct()' => [42], 
        'argName' => 'value',
        'setX()' => [42],
    ],
    'closure' => function($container) {
        return new MyClass($container->get('db'));
    },
    'static_call' => [MyFactory::class, 'create'],
    'object' => new MyClass(),
];
```

Interface definition simply maps an id, that is usually an interface, to particular class.

Full definition describes how to instantiate a class in detail:

  - `__class` contains name of the class to be instantiated.
  - `__construct()` holds an array of constructor arguments.
  - The rest of the config and property values and method calls.
    They are set/called in the order they are in the array.
    
Closures are useful if instantiation is tricky and should be described in code.
In case it is very tricky it's a good idea to move such code into a factory
and referencing it as a static call.

While it's usually not a good idea, you can set already instantiated object into container.

Additionally, definitions could be added via calling `set()`:

```php
$container->set($id, Example::class);

$container->set($id, [
    '__class' => Example::class,
    '__construct()' => ['a', 'b'],
    'property1' => 'val1',
    'setMethod()' => 'val2',
    'property2' => 'val3',
]);
```

After container is configured, dependencies could be obtained via `get()`:

```php
$object = $container->get('interface_name');
```

Note, however, that it is a bad practice to use container directly and it's much better to rely
on autowiring made via Injector available via separate [yiisoft/injector](https://github.com/yiisoft/injector) pacakge.

## Using aliases

Container supports aliases via `Reference` class. It could be useful to have an ability
to retrieve objects both by their interface and named explicitly:

```php
$container = new Container([
    EngineInterface::class => EngineMarkOne::class,
    'engine_one' => EngineInterface::class,
]);
```

## Delegated lookup and composite containers

The `Container` class supports delegated lookup.
When using delegated lookup, all dependencies are always fetched from a given root container.
This allows us to combine multiple containers into a composite container which we can then use for lookups.
When using this approach, one should only use the composite container.

```php
$composite = new CompositeContainer();
$container = new Container([], [], $composite);
```

## Contextual containers

In an application there are several levels at which we might want to have configuration for the DI container.
For example, in Yii application these could be:

- An extension providing default configuration
- An application with configuration
- A module inside the application that uses different configuration than the main application

While in general you never want to inject DI containers into your objects, there are some exceptions such as
Yii application modules that need access to the container.

To support this use case while still supporting custom configuration at the module level we have implemented contextual containers.
The main class is `CompositeContextContainer`; it is like `CompositeContainer` in the sense that it doesn't contain any definitions.
The `attach()` function of the contextual container has an extra string parameter defining the context of the container.

Using context we can create a simple scoping system:

```php
$root = new CompositeContextContainer();
$coreContainer = new Container([], [], $root);
$extensionContainer = new Container([], [], $root);

$appContainer = new Container([
    LoggerInterface::class => MainLogger::class
], [], $root);
$moduleAContainer = new Container([
    LoggerInterface::class => LoggerA::class
], [], $root);
$moduleBContainer = new Container([
    LoggerInterface::class => LoggerB::class
], [], $root);

$composite->attach($moduleContainer, '/moduleB');
$composite->attach($moduleContainer, '/moduleA');
$composite->attach($appContainer);
$composite->attach($extensionContainer);
$composite->attach($coreContainer);

// The composite context container will allow us to create contextual containers with virtually no overhead.
$moduleAContainer = $composite->getContextContainer('/moduleA');
$moduleBContainer = $composite->getContextContainer('/moduleB');

$composite->get(LoggerInterface::class); // MainLogger

$composite->get(LoggerInterface::class); // MainLogger
$moduleAContainer->get(LoggerInterface::class // LoggerA
$moduleBContainer->get(LoggerInterface::class // LoggerB
```

Searching is done using the longest prefix first and then checking the containers in the order in which they were added.
In case of Yii contextual containers for the modules are created automatically. 

## Using service providers

A service provider is a special class that responsible for binding complex services or groups of dependencies 
into the container including registering services with its references, event listeners, middleware etc.

All service providers extend the `Yiisoft\Di\Support\ServiceProvider` class and contain a `register` method. 
Within the register method, you should only bind things into the container. You should never attempt to 
implement in a service provider any business logic, functionality related to environment bootstrap, 
functionality that changes DB or anything else than not related to binding things into the container.
To access the container in a service provider you should use `container` field. Container being passed
to service provider through constructor and saved to `container` field.

Typical service provider could look like:

```php
use Yiisoft\Di\Contracts\ServiceProvider;

class CarFactoryProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->registerDependencies($container);
        $container->registerService($container);
    }

    protected function registerDependencies(Container $container): void
    {
        $container->set(EngineInterface::class, SolarEngine::class);
        $container->set(WheelInterface::class, [
            '__class' => Wheel::class,
            'color' => 'black',
        ]);
        $container->set(CarInterface::class, [
            '__class' => BMW::class,
            'model' => 'X5',
        ]);
    }

    protected function registerService(Container $container): void
    {
        $container->set(CarFactory::class, [
              '__class' => CarFactory::class,
              'color' => 'red',
        ]);
    }
}
```

To add service provider to the container you need either pass service provider class (or configuration array)
to `addProvider` method of the container:

```php
$container->addProvider(CarFactoryProvider::class);
```

or pass it through configuration array using `providers` key:

```php
$container = new Container([
    'providers' => [
        CarFactoryProvider::class,
    ],
]);
```

In the code above we created service provider responsible for bootstrapping of a car factory with all its
dependencies. Once a service providers is added through `addProvider` method or via configuration array, 
`register` method of a service provider is immediately called and services got registered into the container.

**Note**, service provider might decrease performance of your application if you would perform heavy operations
inside the `register` method.

## Using deferred service providers

As stated before, service provider might decrease performance of your application registering heavy services. So
to prevent performance decrease you can use so-called deferred service providers. 

deferred service providers extend the `Yiisoft\Di\Support\DeferredServiceProvider` and in addition to `register` method
contain a `provides` method that returns array with names and identifiers of services service providers bind to 
the container. Deferred service providers being added to the container the same way as regular service providers but 
`register` method of deferred service provider got called only once one of the services listed in `provides` method 
is requested from the container. Example:

```php
use Yiisoft\Di\Support\DeferredServiceProvider;

class CarFactoryProvider extends DeferredServiceProvider
{
    public function provides(): array
    {
        return [
            
            CarFactory::class,
            CarInterface::class,
            EngineInterface::class,
            WheelInterface::class,
        ];
    }
    
    public function register(Container $container): void
    {
        $this->registerDependencies($container);
        $this->registerService($container);
    }

    protected function registerDependencies(Container $container): void
    {
        $container->set(EngineInterface::class, SolarEngine::class);
        $container->set(WheelInterface::class, [
            '__class' => Wheel::class,
            'color' => 'black',
        ]);
        $container->set(CarInterface::class, [
            '__class' => BMW::class,
            'model' => 'X5',
        ]);
    }

    protected function registerService(Container $container): void
    {
        $container->set(CarFactory::class, [
              '__class' => CarFactory::class,
              'color' => 'red',
        ]);
    }
}

$container->addProvider(CarFactoryProvider::class);

// returns false as provider wasn't registered
$container->has(EngineInterface::class); 

// returns SolarEngine, registered in the provider
$engine = $container->get(EngineInterface::class); 

// returns true as provider was registered when EngineInterface was requested from the container
$container->has(EngineInterface::class); 
```

In the code above we added `CarFactoryProvider` to the container but `register` method of `CarFactoryProvider` wasn't 
executed till `EngineInterface` was requested from the container. When we requested `EngineInterface`, container looked at 
`provides` list of the `CarFactoryProvider` and, as `EngineInterface` is listed in `provides`, container called `register`
method of the `CarFactoryProvider`.

**Note**, you can use deferred service providers not just to defer bootstrap of heavy services but also to register your 
services to the container only when they are actually needed. 

## Further reading

- [Martin Fowler's article](http://martinfowler.com/articles/injection.html).
