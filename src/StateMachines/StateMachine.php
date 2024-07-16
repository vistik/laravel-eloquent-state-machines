<?php

namespace Asantibanez\LaravelEloquentStateMachines\StateMachines;

use Asantibanez\LaravelEloquentStateMachines\Exceptions\TransitionNotAllowedException;
use Asantibanez\LaravelEloquentStateMachines\Models\PendingTransition;
use Asantibanez\LaravelEloquentStateMachines\Models\StateHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

abstract class StateMachine
{
    public string $field;

    public Model $model;

    public function __construct(string $field, Model $model)
    {
        $this->field = $field;

        $this->model = $model;
    }

    public function currentState(): string|null
    {
        $field = $this->field;

        return $this->model->$field;
    }

    public function history(): MorphMany
    {
        return $this->model->stateHistory()->forField($this->field);
    }

    public function was($state): bool
    {
        return $this->history()->to($state)->exists();
    }

    public function timesWas($state): int
    {
        return $this->history()->to($state)->count();
    }

    public function whenWas($state): ?Carbon
    {
        $stateHistory = $this->snapshotWhen($state);

        return $stateHistory?->created_at;

    }

    public function snapshotWhen(string $state): ?StateHistory
    {
        return $this->history()->to($state)->latest('id')->first();
    }

    public function snapshotsWhen($state): Collection
    {
        return $this->history()->to($state)->get();
    }

    public function canBe($from, $to): bool
    {
        $availableTransitions = $this->transitions()[$from] ?? [];

        return collect($availableTransitions)->contains($to);
    }

    public function pendingTransitions(): MorphMany
    {
        return $this->model->pendingTransitions()->forField($this->field);
    }

    public function hasPendingTransitions(): bool
    {
        return $this->pendingTransitions()->notApplied()->exists();
    }

    /**
     * @param  array  $customProperties
     * @param  null|mixed  $responsible
     *
     * @throws TransitionNotAllowedException
     * @throws ValidationException
     */
    public function transitionTo(string|null $from, string $to, array $customProperties = [], Model $responsible = null): void
    {
        if ($to === $this->currentState()) {
            return;
        }

        if (! $this->canBe($from, $to) && ! $this->canBe($from, '*') && ! $this->canBe('*', $to) && ! $this->canBe('*', '*')) {
            throw new TransitionNotAllowedException($from, $to, get_class($this->model));
        }

        $validator = $this->validatorForTransition($from, $to, $this->model);
        if ($validator !== null && $validator->fails()) {
            throw new ValidationException($validator);
        }

        $beforeTransitionHooks = $this->beforeTransitionHooks()[$from] ?? [];

        collect($beforeTransitionHooks)
            ->each(function ($callable) use ($to) {
                $callable($to, $this->model);
            });

        $field = $this->field;
        $this->model->$field = $to;

        $changedAttributes = $this->model->getChangedAttributes();

        $this->model->save();

        if ($this->recordHistory()) {
            $responsible = $responsible ?? auth()->user();

            $this->model->recordState($field, $from, $to, $customProperties, $responsible, $changedAttributes);
        }

        $afterTransitionHooks = $this->afterTransitionHooks()[$to] ?? [];

        collect($afterTransitionHooks)
            ->each(function ($callable) use ($from) {
                $callable($from, $this->model);
            });

        $this->cancelAllPendingTransitions();
    }

    /**
     * @param  array  $customProperties
     * @param  null  $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo($from, $to, Carbon $when, $customProperties = [], $responsible = null): ?PendingTransition
    {
        if ($to === $this->currentState()) {
            return null;
        }

        if (! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException($from, $to, get_class($this->model));
        }

        $responsible = $responsible ?? auth()->user();

        return $this->model->recordPendingTransition(
            $this->field,
            $from,
            $to,
            $when,
            $customProperties,
            $responsible
        );
    }

    public function cancelAllPendingTransitions()
    {
        $this->pendingTransitions()->delete();
    }

    abstract public function transitions(): array;

    abstract public function defaultState(): ?string;

    abstract public function recordHistory(): bool;

    public function validatorForTransition($from, $to, $model): ?Validator
    {
        return null;
    }

    public function afterTransitionHooks(): array
    {
        return [];
    }

    public function beforeTransitionHooks(): array
    {
        return [];
    }
}
