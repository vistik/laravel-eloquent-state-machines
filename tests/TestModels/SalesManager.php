<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\TestModels;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\Factories\SalesManagerFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableAlias;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesManager extends Model implements AuthenticatableAlias
{
    use Authenticatable;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): SalesManagerFactory
    {
        return SalesManagerFactory::new();
    }
}
