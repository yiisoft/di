<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Dependency Injection</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/di/v/stable.png)](https://packagist.org/packages/yiisoft/di)
[![Total Downloads](https://poser.pugx.org/yiisoft/di/downloads.png)](https://packagist.org/packages/yiisoft/di)
[![Build status](https://github.com/yiisoft/di/workflows/build/badge.svg)](https://github.com/yiisoft/di/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/di/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/di/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/di/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/di/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdi%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/di/master)
[![static analysis](https://github.com/yiisoft/di/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/di/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/di/coverage.svg)](https://shepherd.dev/github/yiisoft/di)

[PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container that is able to instantiate
and configure classes resolving dependencies.

## Features

- [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible.
- Supports property injection, constructor injection and method injection.
- Detects circular references.
- Accepts array definitions. Could be used with mergeable configs.
- Provides optional autoload fallback for classes without explicit definition.
- Allows delegated lookup and has composite container.
- Supports aliasing.
- Supports service providers.
- Has state resetter for long-running workers serving multiple requests such as [RoadRunner](https://roadrunner.dev/)
  or [Swoole](https://www.swoole.co.uk/).
- Supports container delegates.

## Requirements

- PHP 7.4 or higher.
- `Multibyte String` PHP extension.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/di --prefer-dist
```

## Using the container

Usage of the DI container is fairly simple: You first initialize it with an
array of *definitions*. The array keys are usually interface names. It will
then use these definitions to create an object whenever that type is requested.
This happens for example when fetching a type directly from the container
somewhere in the application. But objects are also created implicitly if a
definition has a dependency to another definition.

Usually a single container is used for the whole application. It is often
configured either in the entry script such as `index.php` or a configuration
file:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withDefinitions($definitions);

$container = new Container($config);
```

The definitions could be stored in a `.php` file that returns an array:

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

As seen above an object can be defined in several ways:

 * In the simple case an interface definition maps an id to a particular class.
 * A full definition describes how to instantiate a class in more detail:
   * `class` contains the name of the class to be instantiated.
   * `__construct()` holds an array of constructor arguments.
   * The rest of the config are property values (prefixed with `$`) and method calls, postfixed with `()`. They are
     set/called in the order they appear in the array.
 * Closures are useful if instantiation is tricky and can better be described in code. When using these, arguments are
   auto-wired by type. `ContainerInterface` could be used to get current container instance.
 * If it is even more complicated, it is a good idea to move such code into a
   factory and reference it as a static call.
 * While it is usually not a good idea, you can also set an already
   instantiated object into the container.

See [yiisoft/definitions](https://github.com/yiisoft/definitions) for more information.

After the container is configured, a service can be obtained via `get()`:

```php
/** @var \Yiisoft\Di\Container $container */
$object = $container->get('interface_name');
```

Note, however, that it is bad practice using a container directly. It is much
better to rely on autowiring as provided by the Injector available from the
[yiisoft/injector](https://github.com/yiisoft/injector) package.


## Using aliases

The DI container supports aliases via the
`Yiisoft\Definitions\Reference` class. This way objects can also be
retrieved by a more handy name:

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

## Composite containers

A composite container combines multiple containers in a single container. When
using this approach, objects should only be fetched from the composite
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

Note, that containers attached earlier override dependencies of containers attached later.

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

A service provider is a special class that is responsible for providing complex
services or groups of dependencies for the container and extensions of existing services. 

A provider should extend from `Yiisoft\Di\ServiceProviderInterface` and must
contain a `getDefinitions()` and `getExtensions()` methods. It should only provide services for the container
and therefore should only contain code that is related to this task. It should *never*
implement any business logic or other functionality such as environment bootstrap or applying changes to database.

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
            // Note that Garage should already be defined in container 
            Garage::class => function(ContainerInterface $container, Garage $garage) {
                $car = $container->get(CarFactory::class)->create();
                $garage->setCar($car);
                
                return $garage;
            }
        ];
    } 
}
```

Here we created a service provider responsible for bootstrapping of a car factory with all its dependencies.

An extension is a callable that returns a modified service object. In our case we get existing `Garage` service
and put a car into the garage by calling the method `setCar()`. Thus, before applying this provider, we had 
an empty garage and with the help of the extension we fill it.

To add this service provider to a container you can pass either its class or a
configuration array in the additional config:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([CarFactoryProvider::class]);

$container = new Container($config);
```

When a service provider is added, its `getDefinitions()` and `getExtensions()` methods are called
*immediately* both services and their extensions get registered into the container.

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

Now we can get tagged services from the container in the following way:

```php
$container->get('tag@car');
```

The result is an array that contains two instances: `BlueCarService` and `RedCarService`.

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

## Resetting services state

Despite stateful services is not a great practice, these are inevitable in many cases. When you build long-running
applications with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/) you should
reset the state of such services every request. For this purpose you can use `StateResetter` with resetters callbacks:

```php
$resetter = new StateResetter();
$resetter->setResetters([
    MyServiceInterface::class => function () {
        $this->reset(); // a method of MyServiceInterface
    },
]);
```

The callback has access to the private and protected properties of the service instance, so you can set initial state
of the service efficiently without creating a new instance. 

The reset itself should be triggered after each request-response cycle. For RoadRunner it would look like the following:

```php
while ($request = $psr7->acceptRequest()) {
    $response = $application->handle($request);
    $psr7->respond($response);
    $application->afterEmit($response);
    $container->get(\Yiisoft\Di\StateResetter::class)->reset();
    gc_collect_cycles();
}
```

### Setting resetters in definitions 

Reset state is defined for each individual service by providing "reset" callback in the following way:

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

Note: resetters from definitons work only if you don't set `StateResetter` in definition or service providers.

### Configuring `StateResetter` manually

To manually add resetters or in case you use Yii DI composite container with a third party container that does
not support state reset natively, state resetter could be configured separately. The following example is PHP-DI:

```php
MyServiceInterface::class => function () {
    // ...
},
StateResetter::class => function () {
    $resetter = new StateResetter();
    $resetter->setResetters([
        MyServiceInterface::class => function () {
            $this->reset(); // a method of MyServiceInterface
        },
    ]);
    return $resetter;
}
```

## Specifying metadata for non-array definitions

In order to specify some metadata, such as in cases of "resetting services state" or "container tags" above, for non-array
definitions, the following syntax could be used:

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

In the above we have explicitly moved definition itself to "definition" key.

## Delegates

Container delegates define. Each delegate is a callable returning a container instance that is used in case a service
can not be found in primary container:

```php
function (ContainerInterface $container): ContainerInterface
{

}
```

In order to configure delegates use additional config:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()->withDelegates([
    function (ContainerInterface $container): ContainerInterface {
        // ...
    }
]);


$container = new Container($config);
```

## Tuning for production

By default, the container validates definitions right when they are set. In production environment, it makes sense to
turn it off:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withValidate(false);

$container = new Container($config);
```

## Strict mode

Container may work in strict mode, i.e. when everything in the container should be defined explicitly. In order to
turn it on use the following code:

```php
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withStrictMode(true);

$container = new Container($config);
```

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


## Command examples

* Default report for all benchmarks that outputs the result to `CSV-file`

`$ ./vendor/bin/phpbench run --report=default --progress=dots  --output=csv_file`

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

* Aggregate report for the `lookup` group that outputs the result to `console` and `CSV-file` 

`$ ./vendor/bin/phpbench run --report=aggregate --progress=dots  --output=csv_file --output=console --group=lookup
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

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

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
