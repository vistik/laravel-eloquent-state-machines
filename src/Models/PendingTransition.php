<?php

namespace Asantibanez\LaravelEloquentStateMachines\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class PendingTransition
 *
 * @property string $field
 * @property string $from
 * @property string $to
 * @property Carbon $transition_at
 * @property Carbon $applied_at
 * @property string $custom_properties
 * @property int $model_id
 * @property string $model_type
 * @property Model $model
 * @property int $responsible_id
 * @property string $responsible_type
 * @property Model $responsible
 */
class PendingTransition extends Model
{
    protected $guarded = [];

    protected $casts = [
        'custom_properties' => 'array',
    ];

    protected $dates = [
        'transition_at' => 'date',
        'applied_at' => 'date',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeNotApplied($query): void
    {
        $query->whereNull('applied_at');
    }

    public function scopeOnScheduleOrOverdue($query): void
    {
        $query->where('transition_at', '<=', now());
    }

    public function scopeForField($query, $field): void
    {
        $query->where('field', $field);
    }
}
