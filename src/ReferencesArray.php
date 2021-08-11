<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\Reference;
use Yiisoft\Factory\Exception\InvalidConfigException;

use function is_string;

final class ReferencesArray
{
    /**
     *  A usage example
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
     * This definition
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
     * equals to
     *
     * ```php
     * //web.php
     *
     * ContentNegotiator::class => [
     *     '__construct()' => [
     *         'contentFormatters' => [
     *             'text/html' => Reference::to(HtmlDataResponseFormatter())),
     *             'application/xml' => Reference::to(XmlDataResponseFormatter()),
     *             'application/json' => Reference::to(JsonDataResponseFormatter()),
     *         ],
     *     ],
     * ],
     * ```
     *
     * @param string[] $ids
     * @return Reference[]
     * @throws InvalidConfigException
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
