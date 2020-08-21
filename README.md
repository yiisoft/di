<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Dependency Injection</h1>
    <br>
</p>

[PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container that is able to instantiate
and configure classes resolving dependencies.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/di/v/stable.png)](https://packagist.org/packages/yiisoft/di)
[![Total Downloads](https://poser.pugx.org/yiisoft/di/downloads.png)](https://packagist.org/packages/yiisoft/di)
[![Build status](https://github.com/yiisoft/di/workflows/build/badge.svg)](https://github.com/yiisoft/di/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/di/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/di/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/di/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/di/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdi%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/di/master)
[![static analysis](https://github.com/yiisoft/di/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/di/actions?query=workflow%3A%22static+analysis%22)

## Features

- [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible.
- Supports property injection, constructor injection and method injection.
- Detects circular references.
- Accepts array definitions. Could be used with mergeable configs.
- Provides autoload fallback for classes without explicit definition.
- Allows delegated lookup and has composite container.
- Supports aliasing.
- Supports service providers and deferred service providers.

## Using container

Usage of DI container is fairly simple. First, you initialize it with array of definitions and then objects created
according to these are used either in the application directly or to resolve dependencies of other definitions.

Usually a single container is used for the whole application so it is often configured in either entry
script such as `index.php` or a configuration file:

```php
use Yiisoft\Di\Container;

$container = new Container($config);
```

Config could be stored in a `.php` file returning array:

```php
return [
    EngineInterface::class => EngineMarkOne::class,
    'full_definition' => [
        '__class' => EngineMarkOne::class,
        '__construct()' => [42], 
        'propertyName' => 'value',
        'setX()' => [42],
    ],
    'closure' => function(ContainerInterface $container) {
        return new MyClass($container->get('db'));
    },
    'static_call' => [MyFactory::class, 'create'],
    'object' => new MyClass(),
];
```

Interface definition maps an id, that is usually an interface, to particular class.

Full definition describes how to instantiate a class in detail:

  - `__class` contains name of the class to be instantiated.
  - `__construct()` holds an array of constructor arguments.
  - The rest of the config and property values and method calls.
    They are set/called in the order they appear in the array.
    
Closures are useful if instantiation is tricky and should be described in code.
If it is even more complicated, it is a good idea to move such code into a factory
and reference it as a static call.

While it is usually not a good idea, you can set already instantiated object into container.

After container is configured, dependencies could be obtained via `get()`:

```php
/** @var \Yiisoft\Di\Container $container */
$object = $container->get('interface_name');
```

Note, however, that it is a bad practice to use container directly and it is better to rely
on autowiring made via Injector available via separate [yiisoft/injector](https://github.com/yiisoft/injector) pacakge.

## Using aliases

Container supports aliases via `Reference` class. It could be useful to have an ability
to retrieve objects both by their interface and named explicitly:

```php
use Yiisoft\Di\Container;

$container = new Container([
    EngineInterface::class => EngineMarkOne::class,
    'engine_one' => EngineInterface::class,
]);
```

## Delegated lookup and composite containers

The `Container` class supports delegated lookup.
When using delegated lookup, all dependencies are always fetched from a given root container.
To use delegate lookup you should set `root container` as a third parameter of the container constructor.

```php
class Car
{
    private EngineInterface $engine;
   
    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }
    
    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }
}
$rootContainer = new Container([
    EngineInterface::class => EngineMarkOne::class
]);
$container = new Container([], [], $rootContainer);
$car = $container->get(Car::class);
$engine = $car->getEngine(); //returns an instance of the `Car` class
```

### Composite containers

Composie container allows combining multiple containers into a composite container, which we can then use for lookups.
When using this approach, one should only use the composite container.

```php
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;

$composite = new CompositeContainer();
$carContainer = new Container([
    EngineInterface::class => EngineMarkOne:class,
    CarInterface::class => Car::class
], []);
$bikeContainer = new Container([
    BikeInterface::class => Bike::class
], []);
$composite->attach($carContainer);
$composite->attach($bikeContainer);
$car = $composite->get(CarInterface::class); //returns an instance of a `Car` class
$bike = $composite->get(BikeInterface::class); //returns an instance of a `Bike` class
```

Note, containers attached later override dependencies of containers attached earlier.

```php
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;

$composite = new CompositeContainer();
$carContainer = new Container([
    EngineInterface::class => EngineMarkOne:class,
    CarInterface::class => Car::class
], []);
$composite->attach($carContainer);
$car = $composite->get(CarInterface::class); //returns an instance of a `Car` class
$engine = $car->getEngine(); //returns an instance of a `EngineMarkOne` class
$engineContainer = new Container([
    EngineInterface::class => EngineMarkTwo:class,
], []);

$composite->attach($engineContainer);
$car = $composite->get(CarInterface::class); //returns an instance of a `Car` class
$engine = $composite->get(EngineInterface::class); //returns an instance of a `EngineMarkTwo` class
```

A composite container can be a `root container` for a container delegate lookup.
```php
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;

$container = new CompositeContainer();
$container1 = new Container([
    'first' => static function () {
        return 'first';
    },
    'third' => static function () {
        return 'third';
    }
]);
$container2 = new Container([
    'second' => static function () {
        return 'second';
    },
    'first-and-second-and-third' => static function ($c) {
        return $c->get('first') . ' ' . $c->get('second') . ' ' . $c->get('third');
    },
]);

$container->attach($container1);
$container->attach($container2);
$first = $container->get('first'); // returns 'first'
$second = $container->get('second'); // returns 'second'
$firstSecondThird = $container->get('first-and-second-and-third'); //returns 'first second third' 
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
use Yiisoft\Di\Container;
use Yiisoft\Di\CompositeContextContainer;

$composite = new CompositeContextContainer();
$coreContainer = new Container([], []);
$extensionContainer = new Container([], []);

$appContainer = new Container([
    LoggerInterface::class => MainLogger::class
], []);
$moduleAContainer = new Container([
    LoggerInterface::class => LoggerA::class
], []);
$moduleBContainer = new Container([
    LoggerInterface::class => LoggerB::class
], []);

$composite->attach($moduleAContainer, '/moduleA');
$composite->attach($moduleBContainer, '/moduleB');
$composite->attach($appContainer);
$composite->attach($extensionContainer);
$composite->attach($coreContainer);

// The composite context container will allow us to create contextual containers with virtually no overhead.
$moduleAContainer = $composite->getContextContainer('/moduleA');
$moduleBContainer = $composite->getContextContainer('/moduleB');

$composite->get(LoggerInterface::class); // MainLogger
$moduleAContainer->get(LoggerInterface::class); // LoggerA
$moduleBContainer->get(LoggerInterface::class); // LoggerB
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
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;

class CarFactoryProvider extends ServiceProvider
{
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
```

To add service provider to the container you need to pass either service provider class or configuration array
as an element of container constructor `$providers` parameter:

```php
use Yiisoft\Di\Container;

$container = new Container($config, [
    CarFactoryProvider::class,  
]);
```

Above we created a service provider responsible for bootstrapping of a car factory with all its
dependencies. Once a service provider added via configuration array, its `register()` method
is immediately called and services got registered into the container.

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
use Yiisoft\Di\Container;
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

$container = new Container($config, [CarFactoryProvider::class]);

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

# Benchmarks

To run benchmarks execute the next command

`composer require phpbench/phpbench` 
`$ ./vendor/bin/phpbench run`

Note: Only works for php 7.4.

Result example

```
\Yiisoft\Di\Tests\Benchmark\ContainerBench

benchConstructStupid....................I4 [μ Mo]/r: 438.566 435.190 (μs) [μSD μRSD]/r: 9.080μs 2.07%
benchConstructSmart.....................I4 [μ Mo]/r: 470.958 468.942 (μs) [μSD μRSD]/r: 2.848μs 0.60%
benchSequentialLookups # 0..............R5 I4 [μ Mo]/r: 2,837.000 2,821.636 (μs) [μSD μRSD]/r: 34.123μs 1.20%
benchSequentialLookups # 1..............R1 I0 [μ Mo]/r: 12,253.600 12,278.859 (μs) [μSD μRSD]/r: 69.087μs 0.56%
benchRandomLookups # 0..................R5 I4 [μ Mo]/r: 3,142.200 3,111.290 (μs) [μSD μRSD]/r: 87.639μs 2.79%
benchRandomLookups # 1..................R1 I2 [μ Mo]/r: 13,298.800 13,337.170 (μs) [μSD μRSD]/r: 103.891μs 0.78%
benchRandomLookupsComposite # 0.........R1 I3 [μ Mo]/r: 3,351.600 3,389.104 (μs) [μSD μRSD]/r: 72.516μs 2.16%
benchRandomLookupsComposite # 1.........R1 I4 [μ Mo]/r: 13,528.200 13,502.881 (μs) [μSD μRSD]/r: 99.997μs 0.74%
\Yiisoft\Di\Tests\Benchmark\ContainerMethodHasBench

benchPredefinedExisting.................R1 I4 [μ Mo]/r: 0.115 0.114 (μs) [μSD μRSD]/r: 0.001μs 1.31%
benchUndefinedExisting..................R5 I4 [μ Mo]/r: 0.436 0.432 (μs) [μSD μRSD]/r: 0.008μs 1.89%
benchUndefinedNonexistent...............R5 I4 [μ Mo]/r: 0.946 0.942 (μs) [μSD μRSD]/r: 0.006μs 0.59%
8 subjects, 55 iterations, 5,006 revs, 0 rejects, 0 failures, 0 warnings 
(best [mean mode] worst) = 0.113 [4,483.856 4,486.051] 0.117 (μs) 
⅀T: 246,612.096μs μSD/r 43.563μs μRSD/r: 1.336%
```
> **Warning!**
> These summary statistics can be misleading. 
> You should always verify the individual subject statistics before drawing any conclusions.

> **Legend**
>
>   * μ:  Mean time taken by all iterations in variant.
>   * Mo: Mode of all iterations in variant.
>   * μSD: μ standard deviation.
>   * μRSD: μ relative standard deviation.
>   * best: Maximum time of all iterations (minimal of all iterations).
>   * mean: Mean time taken by all iterations.
>   * mode: Mode of all iterations.
>   * worst: Minimum time of all iterations (minimal of all iterations).

## Commands examples

* Default report for all benchmarks that outputs the result to `HTML-file` and `MD-file`

`$ ./vendor/bin/phpbench run --report=default --progress=dots  --output=md_file --output=html_file`

Generated MD-file example

>DI benchmark report
>===================
>
>### suite: 1343b1dc0589cb4e985036d14b3e12cb430a975b, date: 2020-02-21, stime: 16:02:45
>
>benchmark | subject | set | revs | iter | mem_peak | time_rev | comp_z_value | comp_deviation
> --- | --- | --- | --- | --- | --- | --- | --- | --- 
>ContainerBench | benchConstructStupid | 0 | 1000 | 0 | 1,416,784b | 210.938μs | -1.48σ | -1.1%
>ContainerBench | benchConstructStupid | 0 | 1000 | 1 | 1,416,784b | 213.867μs | +0.37σ | +0.27%
>ContainerBench | benchConstructStupid | 0 | 1000 | 2 | 1,416,784b | 212.890μs | -0.25σ | -0.18%
>ContainerBench | benchConstructStupid | 0 | 1000 | 3 | 1,416,784b | 215.820μs | +1.60σ | +1.19%
>ContainerBench | benchConstructStupid | 0 | 1000 | 4 | 1,416,784b | 212.891μs | -0.25σ | -0.18%
>ContainerBench | benchConstructSmart | 0 | 1000 | 0 | 1,426,280b | 232.422μs | -1.03σ | -0.5%
>ContainerBench | benchConstructSmart | 0 | 1000 | 1 | 1,426,280b | 232.422μs | -1.03σ | -0.5%
>ContainerBench | benchConstructSmart | 0 | 1000 | 2 | 1,426,280b | 233.398μs | -0.17σ | -0.08%
>ContainerBench | benchConstructSmart | 0 | 1000 | 3 | 1,426,280b | 234.375μs | +0.69σ | +0.33%
>ContainerBench | benchConstructSmart | 0 | 1000 | 4 | 1,426,280b | 235.351μs | +1.54σ | +0.75%
>`... skipped` | `...` | `...` | `...` | `...` | `...` | `...` | `...` | `...`
>ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 0 | 1,216,144b | 81.055μs | -0.91σ | -1.19%
>ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 1 | 1,216,144b | 83.985μs | +1.83σ | +2.38%
>ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 2 | 1,216,144b | 82.032μs | 0.00σ | 0.00%
>ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 3 | 1,216,144b | 82.031μs | 0.00σ | 0.00%
>ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 4 | 1,216,144b | 81.055μs | -0.91σ | -1.19%
>`... skipped` | `...` | `...` | `...` | `...` | `...` | `...` | `...` | `...`

> **Legend**
>
>   * benchmark: Benchmark class.
>   * subject: Benchmark class method.
>   * set: Set of data (provided by ParamProvider).
>   * revs: Number of revolutions (represent the number of times that the code is executed).
>   * iter: Number of iteration.  
>   * mem_peak: (mean) Peak memory used by iteration as retrieved by memory_get_peak_usage.
>   * time_rev:  Mean time taken by all iterations in variant.
>   * comp_z_value: Z-score.
>   * comp_deviation: Relative deviation (margin of error).

* Aggregate report for the `lookup` group that outputs the result to `console` and` MD-file` 

`$ ./vendor/bin/phpbench run --report=aggregate --progress=dots  --output=md_file --output=console --group=lookup
`

>**Notice**
>  
> Available groups: `construct` `lookup` `has` 

Generated MD-file example

> DI benchmark report
> ===================
>
>### suite: 1343b1d2654a3819c72a96d236302b70a504dac7, date: 2020-02-21, stime: 13:27:32
>
>benchmark | subject | set | revs | its | mem_peak | best | mean | mode | worst | stdev | rstdev | diff
> --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- 
>ContainerBench | benchSequentialLookups | 0 | 1000 | 5 | 1,454,024b | 168.945μs | 170.117μs | 169.782μs | 171.875μs | 0.957μs | 0.56% | 1.00x
>ContainerBench | benchSequentialLookups | 1 | 1000 | 5 | 1,445,296b | 3,347.656μs | 3,384.961μs | 3,390.411μs | 3,414.062μs | 21.823μs | 0.64% | 19.90x
>ContainerBench | benchSequentialLookups | 2 | 1000 | 5 | 1,445,568b | 3,420.898μs | 3,488.477μs | 3,447.260μs | 3,657.227μs | 85.705μs | 2.46% | 20.51x
>ContainerBench | benchRandomLookups | 0 | 1000 | 5 | 1,454,024b | 169.922μs | 171.875μs | 171.871μs | 173.828μs | 1.381μs | 0.80% | 1.01x
>ContainerBench | benchRandomLookups | 1 | 1000 | 5 | 1,445,296b | 3,353.515μs | 3,389.844μs | 3,377.299μs | 3,446.289μs | 31.598μs | 0.93% | 19.93x
>ContainerBench | benchRandomLookups | 2 | 1000 | 5 | 1,445,568b | 3,445.313μs | 3,587.696μs | 3,517.823μs | 3,749.023μs | 115.850μs | 3.23% | 21.09x
>ContainerBench | benchRandomLookupsComposite | 0 | 1000 | 5 | 1,454,032b | 297.852μs | 299.610μs | 298.855μs | 302.734μs | 1.680μs | 0.56% | 1.76x
>ContainerBench | benchRandomLookupsComposite | 1 | 1000 | 5 | 1,445,880b | 3,684.570μs | 3,708.984μs | 3,695.731μs | 3,762.695μs | 28.297μs | 0.76% | 21.80x
>ContainerBench | benchRandomLookupsComposite | 2 | 1000 | 5 | 1,446,152b | 3,668.946μs | 3,721.680μs | 3,727.407μs | 3,765.625μs | 30.881μs | 0.83% | 21.88x

> **Legend**
>
>   * benchmark: Benchmark class.
>   * subject: Benchmark class method.
>   * set: Set of data (provided by ParamProvider).
>   * revs: Number of revolutions (represent the number of times that the code is executed).
>   * its: Number of iterations (one measurement for each iteration).   
>   * mem_peak: (mean) Peak memory used by each iteration as retrieved by memory_get_peak_usage.
>   * best: Maximum time of all iterations in variant.
>   * mean: Mean time taken by all iterations in variant.
>   * mode: Mode of all iterations in variant.
>   * worst: Minimum time of all iterations in variant.
>   * stdev: Standard deviation.
>   * rstdev: The relative standard deviation.
>   * diff: Difference between variants in a single group.
