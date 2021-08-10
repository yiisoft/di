<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\Reference;
use Yiisoft\Factory\Exception\InvalidConfigException;

final class ReferencesArray
{
    /**
     * @param array $ids
     * @return array
     * @throws InvalidConfigException
     */
    static function from(array $ids)
    {
        $references = [];

        foreach ($ids as $id) {
            if (!is_string($id)) {
                throw new InvalidConfigException('Values of an array must be string alias or class name.');
            }
            $references[] = Reference::to($id);
        }

        return $references;
    }
}
