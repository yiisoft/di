<?php
namespace Yiisoft\Di\Tests\Benchmark;

/**
 * @Iterations(5)
 */
class TestBench
{

    /**
     * @Revs(1000)
     */
    public function benchTime(): void
    {
        usleep(200);
    }
}
