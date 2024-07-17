<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestEnums\StatusEnum;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithEnumCastingStateMachine;

class SalesOrderWithEnumCasting extends SalesOrder
{
    protected $casts = [
        'status' => StatusEnum::class,
    ];

    public array $stateMachines = [
        'status' => StatusWithEnumCastingStateMachine::class,
    ];
}
