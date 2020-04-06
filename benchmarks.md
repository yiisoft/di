DI benchmark report
===================

### suite: 1343bd6191c6668ccf8fb4cf1a51a7b61159c825, date: 2020-04-06, stime: 20:48:28

benchmark | subject | set | revs | its | mem_peak | best | mean | mode | worst | stdev | rstdev | diff
 --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- 
ContainerBench | benchSequentialLookups | 0 | 1000 | 5 | 1,460,456b | 615.235μs | 625.586μs | 621.205μs | 636.719μs | 7.948μs | 1.27% | 1.00x
ContainerBench | benchSequentialLookups | 1 | 1000 | 5 | 1,467,608b | 634.765μs | 645.507μs | 642.453μs | 661.132μs | 8.691μs | 1.35% | 1.03x
ContainerBench | benchSequentialLookups | 2 | 1000 | 5 | 1,467,752b | 632.813μs | 638.086μs | 636.211μs | 646.484μs | 4.605μs | 0.72% | 1.02x
ContainerBench | benchRandomLookups | 0 | 1000 | 5 | 1,460,456b | 609.375μs | 625.000μs | 633.784μs | 639.649μs | 12.628μs | 2.02% | 1.00x
ContainerBench | benchRandomLookups | 1 | 1000 | 5 | 1,467,608b | 644.531μs | 650.391μs | 652.084μs | 657.227μs | 4.744μs | 0.73% | 1.04x
ContainerBench | benchRandomLookups | 2 | 1000 | 5 | 1,467,752b | 638.672μs | 649.219μs | 650.291μs | 658.203μs | 6.250μs | 0.96% | 1.04x
ContainerBench | benchRandomLookupsComposite | 0 | 1000 | 5 | 24,786,136b | 699.219μs | 708.398μs | 711.499μs | 715.820μs | 5.943μs | 0.84% | 1.13x
ContainerBench | benchRandomLookupsComposite | 1 | 1000 | 5 | 24,780,496b | 743.164μs | 753.125μs | 748.496μs | 772.461μs | 10.268μs | 1.36% | 1.20x
ContainerBench | benchRandomLookupsComposite | 2 | 1000 | 5 | 24,780,640b | 739.257μs | 747.071μs | 744.033μs | 755.860μs | 6.020μs | 0.81% | 1.20x

