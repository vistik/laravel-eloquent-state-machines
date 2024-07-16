<?php

namespace Asantibanez\LaravelEloquentStateMachines\Exceptions;

use Exception;

class InvalidStartingStateException extends Exception
{
    public function __construct(string $expectedState, string $actualState)
    {
        $message = "Expected: $expectedState. Actual: $actualState";

        parent::__construct($message);
    }
}
