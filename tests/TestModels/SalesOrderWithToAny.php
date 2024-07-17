<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusToAnyStateMachine;

class SalesOrderWithToAny extends SalesOrder
{
    public array $stateMachines = [
        'status' => StatusToAnyStateMachine::class,
    ];
}
