<?php


namespace yii\di;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;

class Reference implements DependencyInterface
{
    private $id;

    private function __construct($id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public static function to(string $id)
    {
        return new self($id);
    }

    /**
     * @param AbstractContainer $container
     */
    public function resolve(AbstractContainer $container)
    {
        return $container->get($this->id);
    }
}
