<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\DynamicReference;
use Yiisoft\Factory\Exception\InvalidConfigException;

final class DynamicReferencesArray
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
     *         'contentFormatters' => DynamicReferencesArray::from($params['yiisoft/data-response']['contentFormatters']),
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
     *             'text/html' => DynamicReference::to(HtmlDataResponseFormatter())),
     *             'application/xml' => DynamicReference::to(XmlDataResponseFormatter()),
     *             'application/json' => DynamicReference::to(JsonDataResponseFormatter()),
     *         ],
     *     ],
     * ],
     * ```
     *
     * @param array $ids
     * @return array
     * @throws InvalidConfigException
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
