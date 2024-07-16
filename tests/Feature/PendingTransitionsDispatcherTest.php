<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\Feature;

use Asantibanez\LaravelEloquentStateMachines\Jobs\PendingTransitionExecutor;
use Asantibanez\LaravelEloquentStateMachines\Jobs\PendingTransitionsDispatcher;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestCase;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Queue;

class PendingTransitionsDispatcherTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    #[Test]
    public function should_dispatch_pending_transition()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $pendingTransition =
            $salesOrder->status()->postponeTransitionTo('approved', Carbon::now()->subSecond());

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        //Act
        (new PendingTransitionsDispatcher)->handle();

        //Assert
        $salesOrder->refresh();

        $this->assertFalse($salesOrder->status()->hasPendingTransitions());

        Queue::assertPushed(PendingTransitionExecutor::class, function ($job) use ($pendingTransition) {
            $this->assertEquals($pendingTransition->id, $job->pendingTransition->id);

            return true;
        });
    }

    #[Test]
    public function should_not_dispatch_future_pending_transitions()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $salesOrder->status()->postponeTransitionTo('approved', Carbon::tomorrow());

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        //Act
        (new PendingTransitionsDispatcher)->handle();

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        Queue::assertNothingPushed();
    }
}
