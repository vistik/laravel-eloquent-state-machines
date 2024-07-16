<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\Factories\SalesOrderFactory;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStateMachine;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusStateMachine;
use Asantibanez\LaravelEloquentStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasStateMachines;
    use HasFactory;

    protected $guarded = [];

    public $stateMachines = [
        'status' => StatusStateMachine::class,
        'fulfillment' => FulfillmentStateMachine::class,
    ];

    protected static function newFactory(): SalesOrderFactory
    {
        return SalesOrderFactory::new();
    }
}
