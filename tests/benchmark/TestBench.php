<?php


namespace yii\di\tests\benchmark;

/**
 * @Iterations(5)
 */
class TestBench
{

    /**
     * @Revs(1000)
     */
    public function benchTime()
    {
        usleep(200);
    }
}
