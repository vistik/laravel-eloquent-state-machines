<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusAnyToAnyStateMachine;

class SalesOrderWithAnyToAny extends SalesOrder
{
    public array $stateMachines = [
        'status' => StatusAnyToAnyStateMachine::class,
    ];
}
