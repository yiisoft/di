<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function is_string;

/**
 * Allows creating an array of references from key-reference pairs.
 *
 * @see Reference
 */
final class ReferencesArray
{
    /**
     * Create references array from name-reference pairs.
     *
     * For example if we want to define a set of named references, usually
     * it is done as:
     *
     * ```php
     * //web.php
     *
     * ContentNegotiator::class => [
     *     '__construct()' => [
     *         'contentFormatters' => [
     *             'text/html' => Reference::to(HtmlDataResponseFormatter::class),
     *             'application/xml' => Reference::to(XmlDataResponseFormatter::class),
     *             'application/json' => Reference::to(JsonDataResponseFormatter::class),
     *         ],
     *     ],
     * ],
     * ```
     * That is not very convenient so we can define formatters in a separate config and without explicitly using
     * `Reference::to()` for each formatter:
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
     *         'contentFormatters' => ReferencesArray::from($params['yiisoft/data-response']['contentFormatters']),
     *     ],
     * ],
     * ```
     *
     * @param string[] $ids Name-reference pairs.
     *
     * @throws InvalidConfigException
     *
     * @return Reference[]
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function from(array $ids)
    {
        $references = [];

        foreach ($ids as $key => $id) {
            if (!is_string($id)) {
                throw new InvalidConfigException('Values of an array must be string alias or class name.');
            }
            $references[$key] = Reference::to($id);
        }

        return $references;
    }
}
