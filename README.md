<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Dependency Injection</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/di/v)](https://packagist.org/packages/yiisoft/di)
[![Total Downloads](https://poser.pugx.org/yiisoft/di/downloads)](https://packagist.org/packages/yiisoft/di)
[![Build status](https://github.com/yiisoft/di/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/di/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/di/graph/badge.svg?token=P8W1UTwgQt)](https://codecov.io/gh/yiisoft/di)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdi%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/di/master)
[![static analysis](https://github.com/yiisoft/di/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/di/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/di/coverage.svg)](https://shepherd.dev/github/yiisoft/di)

[PSR-11](https://www.php-fig.org/psr/psr-11/) compatible
[dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) container that's able to instantiate
and configure classes resolving dependencies.

## Features

- [PSR-11](https://www.php-fig.org/psr/psr-11/) compatible.
- Supports property injection, constructor injection, and method injection.
- Detects circular references.
- Accepts array definitions. You can use it with mergeable configs.
- Provides optional autoload fallback for classes without explicit definition.
- Allows delegated lookup and has a composite container.
- Supports aliasing.
- Supports service providers.
- Has state resetter for long-running workers serving many requests, such as [RoadRunner](https://roadrunner.dev/)
  or [Swoole](https://www.swoole.co.uk/).
- Supports container delegates.
- Does auto-wiring.

## Requirements

- PHP 8.1 or higher.
- `Multibyte String` PHP extension.

## Installation

You could install the package with composer:

```shell
composer require yiisoft/di
```

## Using the container

Usage of the DI container is simple: You first initialize it with an
array of *definitions*. The array keys are usually interface names. It will
then use these definitions to create an object whenever the application requests that type.
This happens, for example, when fetching a type directly from the container
somewhere in the application. But objects are also created implicitly if a
definition has a dependency on another definition.

Usually one uses a single container for the whole application. It's often
configured either in the entry script such as `index.php` or a configuration
file:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDefinitions($definitions);

$container = new Container($config);
```

You could store the definitions in a `.php` file that returns an array:

```php
return [
    EngineInterface::class => EngineMarkOne::class,
    'full_definition' => [
        'class' => EngineMarkOne::class,
        '__construct()' => [42], 
        '$propertyName' => 'value',
        'setX()' => [42],
    ],
    'closure' => fn (SomeFactory $factory) => $factory->create('args'),
    'static_call_preferred' => fn () => MyFactory::create('args'),
    'static_call_supported' => [MyFactory::class, 'create'],
    'object' => new MyClass(),
];
```

You can define an object in several ways:

- In the simple case, an interface definition maps an id to a particular class.
- A full definition describes how to instantiate a class in more detail:
  - `class` has the name of the class to instantiate.
  - `__construct()` holds an array of constructor arguments.
  - The rest of the config is property values (prefixed with `$`) and method calls, postfixed with `()`. They're
     set/called in the order they appear in the array.
- Closures are useful if instantiation is tricky and can be better done in code. When using these, arguments are
   auto-wired by type. `ContainerInterface` could be used to get current container instance.
- If it's even more complicated, it's a good idea to move such a code into a
   factory and reference it as a static call.
- While it's usually not a good idea, you can also set an already
   instantiated object into the container.

See [yiisoft/definitions](https://github.com/yiisoft/definitions) for more information.

After you configure the container, you can obtain a service via `get()`:

```php
/** @var \Yiisoft\Di\Container $container */
$object = $container->get('interface_name');
```

Note, however, that it's bad practice using a container directly. It's much
better to rely on auto-wiring as provided by the Injector available from the
[yiisoft/injector](https://github.com/yiisoft/injector) package.

## Using aliases

The DI container supports aliases via the `Yiisoft\Definitions\Reference` class.
This way you can retrieve objects by a more handy name:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDefinitions([
        EngineInterface::class => EngineMarkOne::class,
        'engine_one' => EngineInterface::class,
    ]);

$container = new Container($config);
$object = $container->get('engine_one');
```

## Using class aliases for specific configuration

To define another instance of a class with specific configuration, you can
use native PHP `class_alias()`:

```php
class_alias(Yiisoft\Db\Pgsql\Connection::class, 'MyPgSql');

$config = ContainerConfig::create()                                                                                                                                                     
    ->withDefinitions([
        MyPgSql::class => [ ... ]
    ]);                                                                                                                                                                                 

$container = new Container($config);
$object = $container->get(MyPgSql::class);
```

It could be then conveniently used by type-hinting:

```php
final class MyService
{
    public function __construct(MyPgSql $myPgSql)
    {
        // ...    
    }
} 
```

## Composite containers

A composite container combines many containers in a single container. When
using this approach, you should fetch objects only from the composite
container.

```php
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$composite = new CompositeContainer();

$carConfig = ContainerConfig::create()
    ->withDefinitions([
        EngineInterface::class => EngineMarkOne::class,
        CarInterface::class => Car::class
    ]);
$carContainer = new Container($carConfig);

$bikeConfig = ContainerConfig::create()
    ->withDefinitions([
        BikeInterface::class => Bike::class
    ]);

$bikeContainer = new Container($bikeConfig);
$composite->attach($carContainer);
$composite->attach($bikeContainer);

// Returns an instance of a `Car` class.
$car = $composite->get(CarInterface::class);
// Returns an instance of a `Bike` class.
$bike = $composite->get(BikeInterface::class);
```

Note that containers attached earlier override dependencies of containers attached later.

```php
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$carConfig = ContainerConfig::create()
    ->withDefinitions([
        EngineInterface::class => EngineMarkOne::class,
        CarInterface::class => Car::class
    ]);

$carContainer = new Container($carConfig);

$composite = new CompositeContainer();
$composite->attach($carContainer);

// Returns an instance of a `Car` class.
$car = $composite->get(CarInterface::class);
// Returns an instance of a `EngineMarkOne` class.
$engine = $car->getEngine();

$engineConfig = ContainerConfig::create()
    ->withDefinitions([
        EngineInterface::class => EngineMarkTwo::class,
    ]);

$engineContainer = new Container($engineConfig);

$composite = new CompositeContainer();
$composite->attach($engineContainer);
$composite->attach($carContainer);

// Returns an instance of a `Car` class.
$car = $composite->get(CarInterface::class);
// Returns an instance of a `EngineMarkTwo` class.
$engine = $composite->get(EngineInterface::class);
```

## Using service providers

A service provider is a special class that's responsible for providing complex
services or groups of dependencies for the container and extensions of existing services.

A provider should extend from `Yiisoft\Di\ServiceProviderInterface` and must
contain a `getDefinitions()` and `getExtensions()` methods. It should only provide services for the container
and therefore should only contain code related to this task. It should *never*
implement any business logic or other functionality such as environment bootstrap or applying changes to a database.

A typical service provider could look like:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ServiceProviderInterface;

class CarFactoryProvider extends ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [
            CarFactory::class => [
                'class' => CarFactory::class,
                '$color' => 'red',
            ], 
            EngineInterface::class => SolarEngine::class,
            WheelInterface::class => [
                'class' => Wheel::class,
                '$color' => 'black',
            ],
            CarInterface::class => [
                'class' => BMW::class,
                '$model' => 'X5',
            ],
        ];    
    }
     
    public function getExtensions(): array
    {
        return [
            // Note that Garage should already be defined in a container 
            Garage::class => function(ContainerInterface $container, Garage $garage) {
                $car = $container
                    ->get(CarFactory::class)
                    ->create();
                $garage->setCar($car);
                
                return $garage;
            }
        ];
    } 
}
```

Here you created a service provider responsible for bootstrapping of a car factory with all its dependencies.

An extension is callable that returns a modified service object.
In this case you get existing `Garage` service
and put a car into the garage by calling the method `setCar()`.
Thus, before applying this provider, you had
an empty garage and with the help of the extension you fill it.

To add this service provider to a container, you can pass either its class or a
configuration array in the extra config:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([CarFactoryProvider::class]);

$container = new Container($config);
```

When you add a service provider, DI calls its `getDefinitions()` and `getExtensions()` methods
*immediately* and both services and their extensions get registered into the container.

## Container tags

You can tag services in the following way:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDefinitions([  
        BlueCarService::class => [
            'class' => BlueCarService::class,
            'tags' => ['car'], 
        ],
        RedCarService::class => [
            'definition' => fn () => new RedCarService(),
            'tags' => ['car'],
        ],
    ]);

$container = new Container($config);
```

Another way to tag services is setting tags via container constructor:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDefinitions([  
        BlueCarService::class => [
            'class' => BlueCarService::class,
        ],
        RedCarService::class => fn () => new RedCarService(),
    ])
    ->withTags([
        // "car" tag has references to both blue and red cars
        'car' => [BlueCarService::class, RedCarService::class]
    ]);

$container = new Container($config);
```

### Getting tagged services

You can get tagged services from the container in the following way:

```php
$container->get(\Yiisoft\Di\Reference\TagReference::id('car'));
```

The result is an array that has two instances: `BlueCarService` and `RedCarService`.

### Using tagged services in configuration

Use `TagReference` to get tagged services in configuration:

```php
[
    Garage::class => [
        '__construct()' => [
            \Yiisoft\Di\Reference\TagReference::to('car'),
        ],    
    ],
],
```

## Resetting services state

Despite stateful services isn't a great practice, these are often inevitable. When you build long-running
applications with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/) you should
reset the state of such services every request. For this purpose you can use `StateResetter` with resetters callbacks:

```php
$resetter = new StateResetter($container);
$resetter->setResetters([
    MyServiceInterface::class => function () {
        $this->reset(); // a method of MyServiceInterface
    },
]);
```

The callback has access to the private and protected properties of the service instance,
so you can set the initial state of the service efficiently without creating a new instance.

You should trigger the reset itself after each request-response cycle. For RoadRunner, it would look like the following:

```php
while ($request = $psr7->acceptRequest()) {
    $response = $application->handle($request);
    $psr7->respond($response);
    $application->afterEmit($response);
    $container
        ->get(\Yiisoft\Di\StateResetter::class)
        ->reset();
    gc_collect_cycles();
}
```

### Setting resetters in definitions

You define the reset state for each service by providing "reset" callback in the following way:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDefinitions([
        EngineInterface::class => EngineMarkOne::class,
        EngineMarkOne::class => [
            'class' => EngineMarkOne::class,
            'setNumber()' => [42],
            'reset' => function () {
                $this->number = 42;
            },
        ],
    ]);

$container = new Container($config);
```

Note: resetters from definitions work only if you don't set `StateResetter` in definition or service providers.

### Configuring `StateResetter` manually

To manually add resetters or in case you use Yii DI composite container with a third party container that doesn't support state reset natively, you could configure state resetter separately. The following example is PHP-DI:

```php
MyServiceInterface::class => function () {
    // ...
},
StateResetter::class => function (ContainerInterface $container) {
    $resetter = new StateResetter($container);
    $resetter->setResetters([
        MyServiceInterface::class => function () {
            $this->reset(); // a method of MyServiceInterface
        },
    ]);
    return $resetter;
}
```

## Specifying metadata for non-array definitions

To specify some metadata, such as in cases of "resetting services state" or "container tags," for non-array
definitions, you could use the following syntax:

```php
LogTarget::class => [
    'definition' => static function (LoggerInterface $logger) use ($params) {
        $target = ...
        return $target;
    },
    'reset' => function () use ($params) {
        ...
    },
],
```

Now you've explicitly moved the definition itself to "definition" key.

## Delegates

Each delegate is a callable returning a container instance that's used in case DI
can't find a service in a primary container:

```php
function (ContainerInterface $container): ContainerInterface
{

}
```

To configure delegates use extra config:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDelegates([
        function (ContainerInterface $container): ContainerInterface {
            // ...
        }
    ]);


$container = new Container($config);
```

## Tuning for production

By default, the container validates definitions right when they're set. In the production environment, it makes sense to
turn it off:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withValidate(false);

$container = new Container($config);
```

## Strict mode

Container may work in a strict mode, that's when you should define everything in the container explicitly.
To turn it on, use the following code:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withStrictMode(true);

$container = new Container($config);
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Dependency Injection is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
