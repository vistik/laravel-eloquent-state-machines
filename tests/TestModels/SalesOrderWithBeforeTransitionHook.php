<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStateMachine;

class SalesOrderWithBeforeTransitionHook extends SalesOrder
{
    public array $stateMachines = [
        'status' => StatusWithBeforeTransitionHookStateMachine::class,
    ];
}
