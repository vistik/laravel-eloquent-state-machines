<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusFromAnyStateMachine;

class SalesOrderWithFromAny extends SalesOrder
{
    public array $stateMachines = [
        'status' => StatusFromAnyStateMachine::class,
    ];
}
