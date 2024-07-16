<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\Feature;

use Asantibanez\LaravelEloquentStateMachines\Jobs\PendingTransitionExecutor;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestCase;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesManager;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;

class PendingTransitionExecutorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function should_apply_pending_transition()
    {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $salesOrder = SalesOrder::factory()->create();

        $pendingTransition = $salesOrder->status()->postponeTransitionTo(
            'approved',
            Carbon::now(),
            ['comments' => 'All good!'],
            $responsible = $salesManager
        );

        $this->assertTrue($salesOrder->status()->is('pending'));

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        Queue::after(function (JobProcessed $event) {
            $this->assertFalse($event->job->hasFailed());
        });

        //Act
        PendingTransitionExecutor::dispatch($pendingTransition);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is('approved'));

        $this->assertEquals('All good!', $salesOrder->status()->getCustomProperty('comments'));

        $this->assertEquals($salesManager->id, $salesOrder->status()->responsible()->id);

        $this->assertFalse($salesOrder->status()->hasPendingTransitions());
    }

    #[Test]
    public function should_fail_job_automatically_if_starting_transition_is_not_the_same_as_when_postponed()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $salesOrder->status()->postponeTransitionTo('approved', Carbon::now());

        //Manually update state
        $salesOrder->update(['status' => 'processed']);
        $this->assertTrue($salesOrder->status()->is('processed'));

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        Queue::after(function (JobProcessed $event) {
            $this->assertTrue($event->job->hasFailed());
        });

        //Act
        $pendingTransition = $salesOrder->status()->pendingTransitions()->first();

        PendingTransitionExecutor::dispatch($pendingTransition);
    }
}
