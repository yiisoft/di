DI benchmark report
===================

### suite: 1343b1f32adad2d42dafe0bc3214b66cf05fd990, date: 2020-02-23, stime: 12:27:55

benchmark | subject | set | revs | its | mem_peak | best | mean | mode | worst | stdev | rstdev | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- 
ContainerBench | benchConstructStupid | 0 | 1000 | 5 | 1,416,784b | 285.157μs | 289.648μs | 287.393μs | 294.922μs | 3.787μs | 1.31% | 3.56x
ContainerBench | benchConstructSmart | 0 | 1000 | 5 | 1,426,280b | 261.719μs | 265.234μs | 265.237μs | 268.554μs | 2.277μs | 0.86% | 3.26x
ContainerBench | benchSequentialLookups | 0 | 1000 | 5 | 1,453,736b | 204.101μs | 206.250μs | 204.598μs | 208.985μs | 2.261μs | 1.10% | 2.53x
ContainerBench | benchSequentialLookups | 1 | 1000 | 5 | 1,445,008b | 3,888.672μs | 3,988.086μs | 4,012.759μs | 4,036.133μs | 52.169μs | 1.31% | 48.97x
ContainerBench | benchSequentialLookups | 2 | 1000 | 5 | 1,445,280b | 4,038.085μs | 4,078.320μs | 4,051.845μs | 4,178.711μs | 53.039μs | 1.30% | 50.07x
ContainerBench | benchRandomLookups | 0 | 1000 | 5 | 1,453,736b | 217.774μs | 222.656μs | 222.647μs | 227.539μs | 3.149μs | 1.41% | 2.73x
ContainerBench | benchRandomLookups | 1 | 1000 | 5 | 1,445,008b | 3,723.632μs | 3,770.507μs | 3,780.127μs | 3,799.804μs | 25.164μs | 0.67% | 46.29x
ContainerBench | benchRandomLookups | 2 | 1000 | 5 | 1,445,280b | 3,696.289μs | 3,720.117μs | 3,713.374μs | 3,754.882μs | 19.498μs | 0.52% | 45.68x
ContainerBench | benchRandomLookupsComposite | 0 | 1000 | 5 | 1,453,744b | 309.570μs | 315.430μs | 312.825μs | 322.266μs | 4.744μs | 1.50% | 3.87x
ContainerBench | benchRandomLookupsComposite | 1 | 1000 | 5 | 1,445,592b | 3,705.078μs | 3,774.805μs | 3,784.707μs | 3,831.054μs | 41.065μs | 1.09% | 46.35x
ContainerBench | benchRandomLookupsComposite | 2 | 1000 | 5 | 1,445,864b | 3,740.234μs | 3,786.914μs | 3,764.407μs | 3,875.976μs | 48.414μs | 1.28% | 46.50x
ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 5 | 1,216,144b | 81.054μs | 81.445μs | 81.058μs | 83.008μs | 0.781μs | 0.96% | 1.00x
ContainerMethodHasBench | benchUndefinedExisting | 0 | 1000 | 5 | 1,216,144b | 100.586μs | 102.539μs | 103.006μs | 103.516μs | 1.070μs | 1.04% | 1.26x
ContainerMethodHasBench | benchUndefinedNonexistent | 0 | 1000 | 5 | 1,216,152b | 476.563μs | 485.937μs | 489.161μs | 492.188μs | 5.681μs | 1.17% | 5.97x

