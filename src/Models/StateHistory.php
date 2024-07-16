<?php

namespace Asantibanez\LaravelEloquentStateMachines\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class StateHistory
 *
 * @property string $field
 * @property string $from
 * @property string $to
 * @property array $custom_properties
 * @property int $responsible_id
 * @property string $responsible_type
 * @property mixed $responsible
 * @property Carbon $created_at
 * @property array $changed_attributes
 */
class StateHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'custom_properties' => 'array',
        'changed_attributes' => 'array',
    ];

    public function getCustomProperty(string $key)
    {
        return data_get($this->custom_properties, $key, null);
    }

    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    public function allCustomProperties(): array
    {
        return $this->custom_properties ?? [];
    }

    public function changedAttributesNames(): array
    {
        return collect($this->changed_attributes ?? [])->keys()->toArray();
    }

    public function changedAttributeOldValue(string $attribute)
    {
        return data_get($this->changed_attributes, "$attribute.old", null);
    }

    public function changedAttributeNewValue(string $attribute)
    {
        return data_get($this->changed_attributes, "$attribute.new", null);
    }

    public function scopeForField($query, $field): void
    {
        $query->where('field', $field);
    }

    public function scopeFrom(Builder $query, $from): void
    {
        if (is_array($from)) {
            $query->whereIn('from', $from);
        } else {
            $query->where('from', $from);
        }
    }

    public function scopeTransitionedFrom(Builder $query, $from): void
    {
        $query->from($from);
    }

    public function scopeTo(Builder $query, $to): void
    {
        if (is_array($to)) {
            $query->whereIn('to', $to);
        } else {
            $query->where('to', $to);
        }
    }

    public function scopeTransitionedTo(Builder $query, array|string $to)
    {
        $query->to($to);
    }

    public function scopeWithTransition(Builder $query, string $from, array|string $to)
    {
        $query->from($from)->to($to);
    }

    public function scopeWithCustomProperty(Builder $query, string $key, string $operator, ?string $value = null)
    {
        $query->where("custom_properties->{$key}", $operator, $value);
    }

    public function scopeWithResponsible(Builder $query, Model $responsible)
    {
        return $query
            ->where('responsible_id', $responsible->getKey())
            ->where('responsible_type', $responsible->getMorphClass());

    }
}
