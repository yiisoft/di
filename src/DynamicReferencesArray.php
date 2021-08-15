<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\DynamicReference;
use Yiisoft\Factory\Exception\InvalidConfigException;

use function is_string;

/**
 * Allows creating an array of dynamic references from key-reference pairs.
 *
 * @see DynamicReference
 */
final class DynamicReferencesArray
{
    /**
     * Create dynamic references array from name-reference pairs.
     *
     * For example if we want to define a set of named dynamic references, usually
     * it is done as:
     *
     * ```php
     * //web.php
     *
     * ContentNegotiator::class => [
     *     '__construct()' => [
     *         'contentFormatters' => [
     *             'text/html' => DynamicReference::to(HtmlDataResponseFormatter())),
     *             'application/xml' => DynamicReference::to(XmlDataResponseFormatter()),
     *             'application/json' => DynamicReference::to(JsonDataResponseFormatter()),
     *         ],
     *     ],
     * ],
     * ```
     *
     * That is not very convenient so we can define formatters in a separate config and without explicitly using
     * `DynamicReference::to()` for each formatter:
     *
     * ```php
     * //params.php
     * return [
     *      'yiisoft/data-response' => [
     *          'contentFormatters' => [
     *              'text/html' => HtmlDataResponseFormatter::class,
     *              'application/xml' => XmlDataResponseFormatter::class,
     *              'application/json' => JsonDataResponseFormatter::class,
     *          ],
     *      ],
     * ];
     * ```
     *
     * Then we can use it like the following:
     *
     * ```php
     * //web.php
     *
     * ContentNegotiator::class => [
     *     '__construct()' => [
     *         'contentFormatters' => DynamicReferencesArray::from($params['yiisoft/data-response']['contentFormatters']),
     *     ],
     * ],
     * ```
     *
     * @param string[] $ids Name-reference pairs.
     *
     * @throws InvalidConfigException
     *
     * @return DynamicReference[]
     */
    public static function from(array $ids)
    {
        $references = [];

        foreach ($ids as $key => $id) {
            if (!is_string($id)) {
                throw new InvalidConfigException('Values of an array must be string alias or class name.');
            }
            $references[$key] = DynamicReference::to($id);
        }

        return $references;
    }
}
