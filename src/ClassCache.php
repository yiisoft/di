<?php

namespace Yiisoft\Di;

final class ClassCache
{
    private ?string $cachePath = null;

    public function __construct(string $cachePath = null)
    {
        $this->cachePath = $cachePath;
    }

    public function set(string $className, string $classParent, string $classDeclaration): void
    {
        if ($this->cachePath === null) {
            return;
        }
        file_put_contents($this->getClassPath($className, $classParent), "<?php\n\n" . $classDeclaration);
    }

    public function get(string $className, string $classParent): ?string
    {
        if (!file_exists($this->getClassPath($className, $classParent))) {
            return null;
        }
        try {
            return file_get_contents($this->getClassPath($className, $classParent));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getClassPath(string $className, string $classParent): string
    {
        [$classFileName, $classFilePath] = $this->getClassFileNameAndPath($className, $classParent);
        if (!is_dir($classFilePath)) {
            mkdir($classFilePath, 0777, true);
        }
        $path = $classFilePath . DIRECTORY_SEPARATOR . $classFileName;

        return $path;
    }

    public function getClassFileNameAndPath(string $className, string $classParent): array
    {
        $classParts = explode('\\', $className);
        $parentClassParts = explode('\\', $classParent);
        $classFileName = array_pop($classParts) . '.' . array_pop($parentClassParts) . ".php";
        $classFilePath = $this->cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $classParts);

        return [$classFileName, $classFilePath];
    }
}
