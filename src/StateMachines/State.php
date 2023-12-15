<?php

namespace Asantibanez\LaravelEloquentStateMachines\StateMachines;

use Asantibanez\LaravelEloquentStateMachines\Exceptions\TransitionNotAllowedException;
use Asantibanez\LaravelEloquentStateMachines\Models\PendingTransition;
use Asantibanez\LaravelEloquentStateMachines\Models\StateHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Class State
 *
 * @property string $state
 * @property StateMachine $stateMachine
 */
class State
{
    private null|string|UnitEnum $state;

    private StateMachine $stateMachine;

    public function __construct(null|string|UnitEnum $state, StateMachine $stateMachine)
    {
        $this->state = $state;
        $this->stateMachine = $stateMachine;
    }

    public function getState(): null|string
    {
        return $this->normalizeCasting($this->state);
    }

    public function getStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function is($state): bool
    {
        return $this->getState() === $this->normalizeCasting($state);
    }

    public function isNot($state): bool
    {
        return ! $this->is($state);
    }

    public function was($state): bool
    {
        return $this->stateMachine->was($state);
    }

    public function timesWas($state): int
    {
        return $this->stateMachine->timesWas($state);
    }

    public function whenWas($state): null|Carbon
    {
        return $this->stateMachine->whenWas($state);
    }

    public function snapshotWhen($state): null|StateHistory
    {
        return $this->stateMachine->snapshotWhen($state);
    }

    public function snapshotsWhen($state): Collection
    {
        return $this->stateMachine->snapshotsWhen($state);
    }

    public function history(): MorphMany
    {
        return $this->stateMachine->history();
    }

    public function canBe($state): bool
    {
        return $this->stateMachine->canBe($this->getState(), $this->normalizeCasting($state));
    }

    public function pendingTransitions(): MorphMany
    {
        return $this->stateMachine->pendingTransitions();
    }

    public function hasPendingTransitions(): bool
    {
        return $this->stateMachine->hasPendingTransitions();
    }

    public function transitionTo($state, $customProperties = [], $responsible = null): void
    {
        $this->stateMachine->transitionTo(
            from: $this->state,
            to: $this->normalizeCasting($state),
            customProperties: $customProperties,
            responsible: $responsible
        );
    }

    public function normalizeCasting($state)
    {
        return $this->stateMachine->normalizeCasting($state);
    }

    /**
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(string|UnitEnum $state, Carbon $when, array $customProperties = [], null|Model $responsible = null): null|PendingTransition
    {
        return $this->stateMachine->postponeTransitionTo(
            from: $this->state,
            to: $this->normalizeCasting($state),
            when: $when,
            customProperties: $customProperties,
            responsible: $responsible
        );
    }

    public function latest(): null|StateHistory
    {
        return $this->snapshotWhen($this->getState());
    }

    public function getCustomProperty($key): string
    {
        return optional($this->latest())->getCustomProperty($key);
    }

    public function responsible(): Model
    {
        return optional($this->latest())->responsible;
    }

    public function allCustomProperties(): array
    {
        return optional($this->latest())->allCustomProperties();
    }
}
