<?php

namespace Yiisoft\Di;

final class ProxyManager
{
    private ?string $cachePath = null;

    private ClassRenderer $classRenderer;

    private ClassConfigurator $classConfigurtor;

    private ClassCache $classCache;

    public function __construct(string $cachePath = null)
    {
        $this->cachePath = $cachePath;
        $this->classCache = new ClassCache($cachePath);
        $this->classRenderer = new ClassRenderer();
        $this->classConfigurtor = new ClassConfigurator();
    }

    public function createObjectProxyFromInterface(string $interface, string $parentProxyClass, array $constructorArguments = null): ?object
    {
        $className = $interface . 'Proxy';
        [$classFileName] = $this->classCache->getClassFileNameAndPath($className);
        $shortClassName = substr($classFileName, 0, -4);

        if (!($classDeclaration = $this->classCache->get($className))) {
            $classConfig = $this->generateInterfaceProxyClassConfig($this->classConfigurtor->getInterfaceConfig($interface), $parentProxyClass);
            $classDeclaration = $this->classRenderer->render($classConfig);
            $this->classCache->set($className, $classDeclaration);
        }
        if ($this->cachePath === null) {
            eval(str_replace('<?php', '', $classDeclaration));
        } else {
            $path = $this->classCache->getClassPath($className);
            require $path;
        }
        $proxy = new $shortClassName(...$constructorArguments);

        return $proxy;
    }

    private function generateInterfaceProxyClassConfig(ClassConfig $interfaceConfig, string $parentProxyClass): ClassConfig
    {
        $interfaceConfig->isInterface = false;
        $interfaceConfig->parent = $parentProxyClass;
        $interfaceConfig->interfaces = [$interfaceConfig->name];
        $interfaceConfig->shortName .= 'Proxy';
        $interfaceConfig->name .= 'Proxy';
        foreach ($interfaceConfig->methods as $methodIndex => $method) {
            foreach ($method->modifiers as $index => $modifier) {
                if ($modifier == 'abstract') {
                    unset($interfaceConfig->methods[$methodIndex]->modifiers[$index]);
                }
            }
        }

        return $interfaceConfig;
    }
}
