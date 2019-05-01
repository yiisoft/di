<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use yii\di\exceptions\InvalidConfigException;
use Psr\Container\ContainerInterface;
use yii\di\exceptions\InvalidArgumentException;

/**
 * Array containing References
 * 
 * All items containing a reference ([[Reference]], [[ReferencingArray]] or any other 
 * object implementing [[ResolveInterface]] are resolved by the container [[get()]].
 * Items of other type are left unchanged. 
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
class ReferencingArray implements ResolveInterface
{
    
    /**
     * @var array Items of the array
     */
    public $items = [];
    
    /**
     * Constructor
     * @param array $items Items of the array
     */
    public function __construct(?array $items = null)
    {
        $this->items = $items;
    }
    
    /**
     * Creates a instance containing the given items
     * @param array $items 
     */
    public static function items(?array $items = null)
    {
        return new static ($items);
    }
    
    public function __toString()
    {
        'Referencing Array';
    }
    
    /**
     * Returns the instance of the referenced object
     * @param ContainerInterface $container Container to use to resolve the reference
     * @throws InvalidArgumentException if container is missing
     */
    public function get(?ContainerInterface $container = null)
    {
        if (!isset($container)) {
            throw new InvalidArgumentException(
                    'Failed to get instance of "'.(string)$this.'". Parameter "container" is missing');
        }
        
        $result = $this->items;
        if (is_array($result)) {
            foreach ($result as $k => $v) {
                if ($v instanceof ResolveInterface) {
                    $result[$k] = $v->get($container);
                }
            }
        }
        return $result;
    }
    
    /**
     * Returns wether this is a valid reference
     * @return bool `true` if id is set
     */
    public function isDefined()
    {
        return ($this->items !== null);
    }
    
}
