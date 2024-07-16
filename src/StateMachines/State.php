<?php

namespace Asantibanez\LaravelEloquentStateMachines\StateMachines;

use Asantibanez\LaravelEloquentStateMachines\Exceptions\TransitionNotAllowedException;
use Asantibanez\LaravelEloquentStateMachines\Models\PendingTransition;
use Asantibanez\LaravelEloquentStateMachines\Models\StateHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
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

    public function is(null|string|UnitEnum $state): bool
    {
        return $this->getState() === $this->normalizeCasting($state);
    }

    public function isNot(string|UnitEnum $state): bool
    {
        return ! $this->is($state);
    }

    public function was(string|UnitEnum $state): bool
    {
        return $this->stateMachine->was($state);
    }

    public function timesWas(string|UnitEnum $state): int
    {
        return $this->stateMachine->timesWas($state);
    }

    public function whenWas(string|UnitEnum $state): null|Carbon
    {
        return $this->stateMachine->whenWas($state);
    }

    public function snapshotWhen(string|UnitEnum $state): null|StateHistory
    {
        return $this->stateMachine->snapshotWhen($state);
    }

    public function snapshotsWhen(string|UnitEnum $state): Collection
    {
        return $this->stateMachine->snapshotsWhen($state);
    }

    public function history(): MorphMany
    {
        return $this->stateMachine->history();
    }

    public function canBe(string|UnitEnum $state): bool
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

    public function transitionTo(string|UnitEnum $state, array $customProperties = [], null|Authenticatable $responsible = null): void
    {
        $this->stateMachine->transitionTo(
            from: $this->state,
            to: $this->normalizeCasting($state),
            customProperties: $customProperties,
            responsible: $responsible
        );
    }

    public function normalizeCasting(null|string|UnitEnum $state)
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
