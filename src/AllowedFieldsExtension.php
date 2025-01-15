<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class AllowedFieldsExtension extends OperationExtension
{
    use Hookable;

    const MethodName = 'allowedFields';

    public string $configKey = 'query-builder.parameters.fields';

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $helper = new InferHelper;
        $methodCall = Utils::findMethodCall($routeInfo, self::MethodName);

        if (!$methodCall) {
            return;
        }


        $values = $helper->inferValues($methodCall, $routeInfo);
        $groupedValues = $this->groupByDots($values);

        foreach ($groupedValues as $key => $value) {
            if (is_array($value)) {
                $parameter = new Parameter(config($this->configKey) . "[$key]", 'query');

                $parameter
                    ->setSchema(Schema::fromType(new StringType()))
                    ->description('Available fields: '
                        . implode(', ', array_map(fn($val) => '`' . $val . '`', $value))
                        . '. You can include multiple options by separating them with a comma.');

                $halt = $this->runHooks($operation, $parameter);
                if (!$halt) {
                    $operation->addParameters([$parameter]);
                }
            }
        }

        $parameter = new Parameter(config($this->configKey), 'query');

        $parameter
            ->setSchema(Schema::fromType(new StringType()))
            ->description('Available fields: '
                . implode(', ', array_map(fn($value) => '`' . $value . '`', array_filter($groupedValues, fn($value) => is_string($value))))
                . '. You can include multiple options by separating them with a comma.');


        $halt = $this->runHooks($operation, $parameter);
        if (!$halt) {
            $operation->addParameters([$parameter]);
        }
    }

    private function groupByDots(array $input): array
    {
        $result = [];

        foreach ($input as $item) {
            if (strpos($item, '.') === false) {
                // Если точки нет, добавляем как есть
                $result[] = $item;
            } else {
                // Находим последнее вхождение точки
                $lastDotPos = strrpos($item, '.');
                $mainKey = substr($item, 0, $lastDotPos); // Ключ до последней точки
                $value = substr($item, $lastDotPos + 1); // Последний сегмент после точки

                // Добавляем значение к ключу
                if (!isset($result[$mainKey])) {
                    $result[$mainKey] = [];
                }
                $result[$mainKey][] = $value;
            }
        }

        return $result;
    }
}
