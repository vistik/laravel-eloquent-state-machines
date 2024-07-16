<?php

namespace Asantibanez\LaravelEloquentStateMachines\StateMachines;

use Asantibanez\LaravelEloquentStateMachines\Exceptions\TransitionNotAllowedException;
use Asantibanez\LaravelEloquentStateMachines\Models\PendingTransition;
use Asantibanez\LaravelEloquentStateMachines\Models\StateHistory;
use Carbon\Carbon;

/**
 * Class State
 *
 * @property string $state
 * @property StateMachine $stateMachine
 */
class State
{
    private ?string $state;

    private StateMachine $stateMachine;

    public function __construct(?string $state, StateMachine $stateMachine)
    {
        $this->state = $state;
        $this->stateMachine = $stateMachine;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function is($state): bool
    {
        return $this->state === $state;
    }

    public function isNot($state): bool
    {
        return ! $this->is($state);
    }

    public function was($state): bool
    {
        return $this->stateMachine->was($state);
    }

    public function timesWas($state)
    {
        return $this->stateMachine->timesWas($state);
    }

    public function whenWas($state)
    {
        return $this->stateMachine->whenWas($state);
    }

    public function snapshotWhen($state)
    {
        return $this->stateMachine->snapshotWhen($state);
    }

    public function snapshotsWhen($state)
    {
        return $this->stateMachine->snapshotsWhen($state);
    }

    public function history()
    {
        return $this->stateMachine->history();
    }

    public function canBe($state)
    {
        return $this->stateMachine->canBe($from = $this->state, $to = $state);
    }

    public function pendingTransitions()
    {
        return $this->stateMachine->pendingTransitions();
    }

    public function hasPendingTransitions()
    {
        return $this->stateMachine->hasPendingTransitions();
    }

    public function transitionTo($state, $customProperties = [], $responsible = null)
    {
        $this->stateMachine->transitionTo(
            $from = $this->state,
            $to = $state,
            $customProperties,
            $responsible
        );
    }

    /**
     * @param  array  $customProperties
     * @param  null  $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo($state, Carbon $when, $customProperties = [], $responsible = null): ?PendingTransition
    {
        return $this->stateMachine->postponeTransitionTo(
            $from = $this->state,
            $to = $state,
            $when,
            $customProperties,
            $responsible
        );
    }

    public function latest(): ?StateHistory
    {
        return $this->snapshotWhen($this->state);
    }

    public function getCustomProperty($key)
    {
        return optional($this->latest())->getCustomProperty($key);
    }

    public function responsible()
    {
        return optional($this->latest())->responsible;
    }

    public function allCustomProperties()
    {
        return optional($this->latest())->allCustomProperties();
    }
}
