<?php

namespace Yiisoft\Di;

final class ClassCache
{
    private ?string $cachePath = null;

    public function __construct(string $cachePath = null)
    {
        $this->cachePath = $cachePath;
    }

    public function set(string $className, string $classDeclaration): void
    {
        if ($this->cachePath === null) {
            return;
        }
        file_put_contents($this->getClassPath($className), "<?php\n\n" . $classDeclaration);
    }

    public function get(string $className): ?string
    {
        if (!file_exists($this->getClassPath($className))) {
            return null;
        }
        try {
            return file_get_contents($this->getClassPath($className));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getClassPath(string $className): string
    {
        [$classFileName, $classFilePath] = $this->getClassFileNameAndPath($className);
        if (!is_dir($classFilePath)) {
            mkdir($classFilePath, 0777, true);
        }
        $path = $classFilePath . DIRECTORY_SEPARATOR . $classFileName;

        return $path;
    }

    public function getClassFileNameAndPath(string $className): array
    {
        $classParts = explode('\\', $className);
        $classFileName = array_pop($classParts) . ".php";
        $classFilePath = $this->cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $classParts);

        return [$classFileName, $classFilePath];
    }
}
