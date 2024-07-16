<?php

namespace Asantibanez\LaravelEloquentStateMachines\Exceptions;

use Exception;

class TransitionNotAllowedException extends Exception
{
    protected string|null $from;

    protected string $to;

    protected string $model;

    public function __construct(string|null $from, string $to, string $model)
    {
        $this->from = $from;
        $this->to = $to;
        $this->model = $model;

        parent::__construct("Transition from '$from' to '$to' is not allowed for model '$model'", 422);
    }

    public function getFrom(): string|null
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
