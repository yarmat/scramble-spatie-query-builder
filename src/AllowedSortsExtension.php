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

class AllowedSortsExtension extends OperationExtension
{
    use Hookable;

    const MethodName = 'allowedSorts';

    public string $configKey = 'query-builder.parameters.sort';

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $helper = new InferHelper;

        $methodCall = Utils::findMethodCall($routeInfo, self::MethodName);

        if (!$methodCall) {
            return;
        }

        $values = $helper->inferValues($methodCall, $routeInfo);
        $arrayType = new ArrayType;
        $arrayType->items->enum(array_merge(
            $values,
            array_map(fn($value) => '-' . $value, $values)
        ));

        $parameter = new Parameter(config($this->configKey), 'query');

        $parameter
            ->setSchema(Schema::fromType(new StringType()))
            ->description('Available sorts: '
                . implode(', ', array_map(fn($value) => '`' . $value . '`', $values))
                . '. You can sort by multiple options by separating them with a comma. To sort in descending order, use - sign in front of the sort, for example: `-'
                . $values[0] . '`.');

        $halt = $this->runHooks($operation, $parameter);
        if (!$halt) {
            $operation->addParameters([$parameter]);
        }
    }
}
