<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\Feature;

use Asantibanez\LaravelEloquentStateMachines\Exceptions\TransitionNotAllowedException;
use Asantibanez\LaravelEloquentStateMachines\Models\PendingTransition;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestCase;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesManager;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesOrder;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStateMachine;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusStateMachine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

class HasStateMachinesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function can_configure_state_machines()
    {
        //Act
        $salesOrder = SalesOrder::factory()->create();

        $this->assertEquals(StatusStateMachine::class, $salesOrder->stateMachines['status']);
        $this->assertEquals(FulfillmentStateMachine::class, $salesOrder->stateMachines['fulfillment']);

        //Assert
        $this->assertNotNull($salesOrder->status());

        $this->assertNotNull($salesOrder->fulfillment());
    }

    #[Test]
    public function should_set_default_state_for_field()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        //Act
        $statusStateMachine = new StatusStateMachine('status', $salesOrder);
        $fulfillmentStateMachine = new FulfillmentStateMachine('fulfillment', $salesOrder);

        //Assert
        $this->assertEquals($statusStateMachine->defaultState(), $salesOrder->status);
        $this->assertEquals($statusStateMachine->defaultState(), $salesOrder->status()->getState());
        $this->assertEquals(1, $salesOrder->status()->history()->count());

        $this->assertEquals($fulfillmentStateMachine->defaultState(), $salesOrder->fulfillment);
        $this->assertEquals($fulfillmentStateMachine->defaultState(), $salesOrder->fulfillment()->getState());
        $this->assertEquals(0, $salesOrder->fulfillment()->history()->count());
    }

    #[Test]
    public function should_transition_to_next_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->assertTrue($salesOrder->status()->is('pending'));

        $this->assertEquals('pending', $salesOrder->status);

        //Act
        $salesOrder->status()->transitionTo('approved');

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is('approved'));

        $this->assertEquals('approved', $salesOrder->status);
    }

    #[Test]
    public function should_not_do_anything_when_transitioning_to_same_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->assertTrue($salesOrder->status()->is('pending'));

        $this->assertEquals(1, $salesOrder->status()->history()->count());

        //Act
        $salesOrder->status()->transitionTo('pending');

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is('pending'));

        $this->assertEquals(1, $salesOrder->status()->history()->count());
    }

    #[Test]
    public function should_register_responsible_for_transition_when_specified()
    {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $salesOrder = SalesOrder::factory()->create();

        //Act
        $salesOrder->status()->transitionTo('approved', [], $salesManager);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->status()->responsible();

        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));

        $responsible = $salesOrder->status()->snapshotWhen('approved')->responsible;
        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));
    }

    #[Test]
    public function should_register_auth_as_responsible_for_transition_when_available()
    {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $this->actingAs($salesManager);

        $salesOrder = SalesOrder::factory()->create();

        //Act
        $salesOrder->status()->transitionTo('approved');

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->status()->responsible();

        $this->assertEquals($salesManager->id, $responsible->id);
        $this->assertEquals(SalesManager::class, get_class($responsible));
    }

    #[Test]
    public function can_check_next_possible_transitions()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->assertTrue($salesOrder->status()->is('pending'));

        //Act - Assert
        $this->assertTrue($salesOrder->status()->canBe('approved'));

        $this->assertFalse($salesOrder->status()->canBe('declined'));
    }

    #[Test]
    public function should_throw_exception_for_invalid_state_on_transition()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create([
            'status' => 'approved',
        ]);

        $this->assertFalse($salesOrder->status()->canBe('pending'));

        //Act
        try {
            $salesOrder->status()->transitionTo('pending');
            $this->fail('Should have thrown exception');
        } catch (Throwable $throwable) {
            //Assert
            $this->assertTrue($throwable instanceof TransitionNotAllowedException);
        }
    }

    #[Test]
    public function should_throw_exception_for_custom_validator_on_transition()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->assertTrue($salesOrder->status()->is('pending'));

        $this->assertTrue($salesOrder->fulfillment()->is(null));

        $this->assertTrue($salesOrder->fulfillment()->canBe('pending'));

        //Act
        try {
            $salesOrder->fulfillment()->transitionTo('pending');
            $this->fail('Should have thrown exception');
        } catch (Throwable $throwable) {
            // Assert
            $this->assertTrue($throwable instanceof ValidationException);
        }
    }

    #[Test]
    public function should_record_history_when_transitioning_to_next_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->assertTrue($salesOrder->status()->getStateMachine()->recordHistory());

        $this->assertEquals(1, $salesOrder->status()->history()->count());

        //Act
        $salesOrder->status()->transitionTo('approved');

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(2, $salesOrder->status()->history()->count());
    }

    #[Test]
    public function should_record_history_when_creating_model()
    {
        //Arrange
        $dummySalesOrder = new SalesOrder();

        $stateMachine = new StatusStateMachine('status', $dummySalesOrder);

        $this->assertTrue($stateMachine->recordHistory());

        //Act
        $salesOrder = SalesOrder::factory()->create();

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(1, $salesOrder->status()->history()->count());
    }

    #[Test]
    public function should_save_auth_user_as_responsible_in_record_history_when_creating_model()
    {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $this->actingAs($salesManager);

        //Act
        $salesOrder = SalesOrder::factory()->create();

        //Assert
        $salesOrder->refresh();

        $this->assertEquals($salesManager->id, $salesOrder->status()->responsible()->id);
    }

    #[Test]
    public function should_not_record_history_when_creating_model_if_record_history_turned_off()
    {
        //Arrange
        $dummySalesOrder = new SalesOrder();

        $stateMachine = new FulfillmentStateMachine('fulfillment', $dummySalesOrder);

        $this->assertFalse($stateMachine->recordHistory());

        //Act
        $salesOrder = SalesOrder::factory()->create([
            'fulfillment' => 'pending',
        ]);

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(0, $salesOrder->fulfillment()->history()->count());
    }

    #[Test]
    public function can_record_history_with_custom_properties_when_transitioning_to_next_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        //Act
        $comments = $this->faker->sentence;

        $salesOrder->status()->transitionTo('approved', [
            'comments' => $comments,
        ]);

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is('approved'));

        $this->assertEquals($comments, $salesOrder->status()->getCustomProperty('comments'));
    }

    #[Test]
    public function can_check_if_previous_state_was_transitioned()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        //Act
        $salesOrder->status()->transitionTo('approved');

        $salesOrder->status()->transitionTo('processed');

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->was('approved'));
        $this->assertTrue($salesOrder->status()->was('processed'));

        $this->assertEquals(1, $salesOrder->status()->timesWas('approved'));
        $this->assertEquals(1, $salesOrder->status()->timesWas('processed'));

        $this->assertNotNull($salesOrder->status()->whenWas('approved'));
        $this->assertNotNull($salesOrder->status()->whenWas('processed'));

        $this->assertFalse($salesOrder->status()->was('another_status'));
        $this->assertEquals(0, $salesOrder->status()->was('another_status'));
    }

    #[Test]
    public function can_record_pending_transition()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $salesManager = SalesManager::factory()->create();

        //Act
        $customProperties = [
            'comments' => $this->faker->sentence,
        ];

        $responsible = $salesManager;

        $pendingTransition = $salesOrder->status()->postponeTransitionTo(
            'approved',
            Carbon::tomorrow()->startOfDay(),
            $customProperties,
            $responsible
        );

        //Assert
        $this->assertNotNull($pendingTransition);

        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->is('pending'));

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        /** @var PendingTransition $pendingTransition */
        $pendingTransition = $salesOrder->status()->pendingTransitions()->first();

        $this->assertEquals('status', $pendingTransition->field);
        $this->assertEquals('pending', $pendingTransition->from);
        $this->assertEquals('approved', $pendingTransition->to);

        $this->assertEquals(Carbon::tomorrow()->startOfDay(), $pendingTransition->transition_at);

        $this->assertEquals($customProperties, $pendingTransition->custom_properties);

        $this->assertNull($pendingTransition->applied_at);

        $this->assertEquals($salesOrder->id, $pendingTransition->model->id);

        $this->assertEquals($salesManager->id, $pendingTransition->responsible->id);
    }

    #[Test]
    public function should_not_record_pending_transition_for_same_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        $this->assertTrue($salesOrder->status()->is('pending'));

        //Act
        $pendingTransition = $salesOrder->status()->postponeTransitionTo(
            'pending',
            Carbon::tomorrow()->startOfDay()
        );

        //Assert
        $this->assertNull($pendingTransition);
    }

    #[Test]
    public function should_cancel_all_pending_transitions_when_transitioning_to_next_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        factory(PendingTransition::class)->times(5)->create([
            'field' => 'status',
            'model_id' => $salesOrder->id,
            'model_type' => SalesOrder::class,
        ]);

        factory(PendingTransition::class)->times(5)->create([
            'field' => 'fulfillment',
            'model_id' => $salesOrder->id,
            'model_type' => SalesOrder::class,
        ]);

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());
        $this->assertTrue($salesOrder->fulfillment()->hasPendingTransitions());

        //Act
        $salesOrder->status()->transitionTo('approved');

        //Assert
        $salesOrder->refresh();

        $this->assertFalse($salesOrder->status()->hasPendingTransitions());
        $this->assertTrue($salesOrder->fulfillment()->hasPendingTransitions());
    }

    #[Test]
    public function should_throw_exception_for_invalid_state_on_postponed_transition()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();

        //Act
        try {
            $salesOrder->status()->postponeTransitionTo('invalid', Carbon::tomorrow());
            $this->fail('Should have thrown exception');
        } catch (Throwable $exception) {
            //Assert
            $this->assertTrue($exception instanceof TransitionNotAllowedException);
            $this->assertEquals('pending', $exception->getFrom());
            $this->assertEquals('invalid', $exception->getTo());
            $this->assertEquals(SalesOrder::class, $exception->getModel());
        }
    }
}
