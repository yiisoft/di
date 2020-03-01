<?php

namespace Yiisoft\Di;

final class ClassRenderer
{
    private string $classSignatureTemplate = '{{modifiers}} {{classType}} {{name}} extends {{parent}}{{implements}}';

    private string $proxyMethodSignatureTemplate = '{{modifiers}} function {{name}}({{params}}){{returnType}}';

    private string $proxyMethodBodyTemplate = '{{return}}$this->call({{methodName}}, [{{params}}]);';

    public function render(ClassConfig $classConfig): string
    {
        return trim($this->renderClassSignature($classConfig)) . "\n" . '{' . $this->renderClassBody($classConfig) . '}';
    }

    private function renderClassSignature(ClassConfig $classConfig): string
    {
        return strtr($this->classSignatureTemplate, [
            '{{modifiers}}' => $this->renderModifiers($classConfig->modifiers),
            '{{classType}}' => $classConfig->isInterface ? 'interface' : 'class',
            '{{name}}' => $classConfig->shortName,
            '{{parent}}' => $classConfig->parent,
            '{{implements}}' => $this->renderImplements($classConfig->interfaces),
        ]);
    }

    private function renderImplements(array $interfaces): string
    {
        return $interfaces !== [] ? ' implements '  . implode(' ', $interfaces) : '';
    }

    private function renderModifiers(array $modifiers)
    {
        return implode(' ', $modifiers);
    }

    private function renderClassBody(ClassConfig $classConfig): string
    {
        return $this->renderMethods($classConfig->methods);
    }

    private function renderMethods(array $methods): string
    {
        $methodsCode = '';
        foreach ($methods as $method) {
            $methodsCode .= "\n" . $this->renderMethod($method);
        }

        return $methodsCode;
    }

    private function renderMethod(MethodConfig $method): string
    {
        return $this->renderMethodSignature($method) . "\n" . $this->margin() . '{' . $this->renderMethodBody($method) . $this->margin() . '}' . "\n";
    }

    private function renderMethodSignature(MethodConfig $method): string
    {
        return strtr($this->proxyMethodSignatureTemplate, [
            '{{modifiers}}' => $this->margin() . $this->renderModifiers($method->modifiers),
            '{{name}}' => $method->name,
            '{{params}}' => $this->renderMethodParameters($method->parameters),
            '{{returnType}}' => $this->renderReturnType($method),
        ]);
    }

    private function renderMethodParameters(array $parameters): string
    {
        $params = '';
        foreach ($parameters as $parameter) {
            $params .= $this->renderMethodParameter($parameter) . ', ';
        }

        return rtrim($params, ', ');
    }

    private function renderMethodParameter(ParameterConfig $parameter): string
    {
        return ltrim(($parameter->hasType ? $this->renderType($parameter->type) : '') . ' $' . $parameter->name .
            $this->renderParameterDefaultValue($parameter));
    }

    private function renderParameterDefaultValue(ParameterConfig $parameter): string
    {
        return ($parameter->isDefaultValueAvailable ? ' = ' .
            ($parameter->isDefaultValueConstant ? $parameter->defaultValueConstantName : self::varExport($parameter->defaultValue)) : '');
    }

    private function renderMethodBody(MethodConfig $method): string
    {
        return "\n" . strtr($this->proxyMethodBodyTemplate, [
                '{{return}}' => $this->margin(2) . $this->renderReturn($method),
                '{{methodName}}' => "'" . $method->name . "'",
                '{{params}}' => $this->renderMethodCallParameters($method->parameters)
            ]) . "\n";
    }

    private function renderReturn(MethodConfig $method): string
    {
        return $method->hasReturnType ? ($method->returnType->name === 'void' ? '': 'return ') : 'return ';
    }

    private function renderReturnType(MethodConfig $method): string
    {
        return $method->hasReturnType ? ': ' . $this->renderType($method->returnType) : '';
    }

    private function renderType(TypeConfig $type): string
    {
        return ($type->allowsNull ? '?' : '') . $type->name;
    }

    private function renderMethodCallParameters(array $parameters): string
    {
        $params = array_keys($parameters);
        return $params !== [] ? '$' . implode(', $', $params) : '';
    }

    private static function varExport($var): string
    {
        $output = '';
        switch (gettype($var)) {
            case 'boolean':
                $output = $var ? 'true' : 'false';
                break;
            case 'integer':
                $output = (string)$var;
                break;
            case 'double':
                $output = (string)$var;
                break;
            case 'string':
                $output = "'" . addslashes($var) . "'";
                break;
            case 'NULL':
                $output = 'null';
                break;
            case 'array':
                if (empty($var)) {
                    $output .= '[]';
                } else {
                    $keys = array_keys($var);
                    $output .= '[';
                    foreach ($keys as $key) {
                        $output .= self::varExport($key);
                        $output .= ' => ';
                        $output .= self::varExport($var[$key]);
                    }
                    $output .= ']';
                }
                break;
        }

        return $output;
    }

    private function margin(int $count = 1): string
    {
        return str_repeat('    ', $count);
    }
}
