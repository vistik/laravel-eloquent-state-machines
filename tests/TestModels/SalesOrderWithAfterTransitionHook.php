<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithAfterTransitionHookStateMachine;

class SalesOrderWithAfterTransitionHook extends SalesOrder
{
    public array $stateMachines = [
        'status' => StatusWithAfterTransitionHookStateMachine::class,
    ];
}
