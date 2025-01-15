<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class AllowedFiltersExtension extends OperationExtension
{
    use Hookable;

    const MethodName = 'allowedFilters';

    public string $configKey = 'query-builder.parameters.filter';

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $helper = new InferHelper;

        $methodCall = Utils::findMethodCall($routeInfo, self::MethodName);

        if (! $methodCall) {
            return;
        }

        $values = $helper->inferValues($methodCall, $routeInfo);

        foreach ($values as $value) {
            $parameter = new Parameter(config($this->configKey) . "[$value]", 'query');

            $parameter->setSchema(Schema::fromType(new StringType()))
                ->description('The '.$value.' to filter an item by. Multiple values can be passed, separated via , (`Nike,Tesla`).');

            $halt = $this->runHooks($operation, $parameter);

            if (! $halt) {
                $operation->addParameters([
                    $parameter,
                ]);
            }
        }
    }
}
