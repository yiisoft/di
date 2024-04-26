# Internals

# Benchmarks

To run benchmarks execute the next command

```shell
./vendor/bin/phpbench run
```

Result example

```text
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
> - μ:  Mean time taken by all iterations in variant.
> - Mo: Mode of all iterations in variant.
> - μSD: μ standard deviation.
> - μRSD: μ relative standard deviation.
> - best: Maximum time of all iterations (minimal of all iterations).
> - mean: Mean time taken by all iterations.
> - mode: Mode of all iterations.
> - worst: Minimum time of all iterations (minimal of all iterations).

## Command examples

- Default report for all benchmarks that outputs the result to `CSV-file`

```shell
./vendor/bin/phpbench run --report=default --progress=dots  --output=csv_file
```

Generated MD-file example

```text
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
> - benchmark: Benchmark class.
> - subject: Benchmark class method.
> - set: Set of data (provided by ParamProvider).
> - revs: Number of revolutions (represent the number of times that the code is executed).
> - iter: Number of iteration.
> - mem_peak: (mean) Peak memory used by iteration as retrieved by memory_get_peak_usage.
> - time_rev:  Mean time taken by all iterations in variant.
> - comp_z_value: Z-score.
> - comp_deviation: Relative deviation (margin of error).
```

- Aggregate report for the `lookup` group that outputs the result to `console` and `CSV-file`

```shell
./vendor/bin/phpbench run --report=aggregate --progress=dots  --output=csv_file --output=console --group=lookup
```

>**Notice**
>
> Available groups: `construct` `lookup` `has`

Generated MD-file example

```text
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
```

## Further reading

- [Martin Fowler's article](https://martinfowler.com/articles/injection.html).

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or
use either newest or any specific version of PHP:

```shell
./vendor/bin/rector
```

## Dependencies

Use [ComposerRequireChecker](https://github.com/maglnet/ComposerRequireChecker) to detect transitive
[Composer](https://getcomposer.org/) dependencies.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```
