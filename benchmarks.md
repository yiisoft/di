DI benchmark report
===================

### suite: 1343b1f1e4c3ec4fd054db9f34733d553d218183, date: 2020-02-23, stime: 14:09:17

benchmark | subject | set | revs | its | mem_peak | best | mean | mode | worst | stdev | rstdev | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- 
ContainerBench | benchConstructStupid | 0 | 1000 | 5 | 1,417,200b | 226.563μs | 231.250μs | 228.350μs | 237.304μs | 4.297μs | 1.86% | 2.33x
ContainerBench | benchConstructSmart | 0 | 1000 | 5 | 1,426,696b | 249.023μs | 253.125μs | 254.882μs | 255.859μs | 2.649μs | 1.05% | 2.55x
ContainerBench | benchSequentialLookups | 0 | 1000 | 5 | 1,454,152b | 211.914μs | 215.625μs | 216.101μs | 218.750μs | 2.261μs | 1.05% | 2.17x
ContainerBench | benchSequentialLookups | 1 | 1000 | 5 | 1,445,424b | 3,231.445μs | 3,246.484μs | 3,250.675μs | 3,256.836μs | 8.942μs | 0.28% | 32.66x
ContainerBench | benchSequentialLookups | 2 | 1000 | 5 | 1,445,696b | 3,218.750μs | 3,238.477μs | 3,234.750μs | 3,263.672μs | 14.918μs | 0.46% | 32.58x
ContainerBench | benchRandomLookups | 0 | 1000 | 5 | 1,454,152b | 194.336μs | 196.875μs | 195.433μs | 201.172μs | 2.516μs | 1.28% | 1.98x
ContainerBench | benchRandomLookups | 1 | 1000 | 5 | 1,445,424b | 3,581.054μs | 3,607.617μs | 3,587.712μs | 3,646.485μs | 27.865μs | 0.77% | 36.29x
ContainerBench | benchRandomLookups | 2 | 1000 | 5 | 1,445,696b | 3,552.734μs | 3,576.758μs | 3,568.787μs | 3,607.422μs | 20.462μs | 0.57% | 35.98x
ContainerBench | benchRandomLookupsComposite | 0 | 1000 | 5 | 1,454,160b | 312.500μs | 315.039μs | 313.394μs | 321.289μs | 3.303μs | 1.05% | 3.17x
ContainerBench | benchRandomLookupsComposite | 1 | 1000 | 5 | 1,446,008b | 3,503.906μs | 3,529.492μs | 3,525.990μs | 3,556.640μs | 18.523μs | 0.52% | 35.50x
ContainerBench | benchRandomLookupsComposite | 2 | 1000 | 5 | 1,446,280b | 3,532.227μs | 3,564.844μs | 3,551.886μs | 3,611.328μs | 29.372μs | 0.82% | 35.86x
ContainerMethodHasBench | benchPredefinedExisting | 0 | 1000 | 5 | 1,216,624b | 97.656μs | 99.414μs | 100.080μs | 101.563μs | 1.563μs | 1.57% | 1.00x
ContainerMethodHasBench | benchUndefinedExisting | 0 | 1000 | 5 | 1,216,624b | 101.563μs | 103.125μs | 103.432μs | 104.492μs | 0.996μs | 0.97% | 1.04x
ContainerMethodHasBench | benchUndefinedNonexistent | 0 | 1000 | 5 | 1,216,632b | 470.703μs | 481.445μs | 485.411μs | 486.328μs | 6.359μs | 1.32% | 4.84x

