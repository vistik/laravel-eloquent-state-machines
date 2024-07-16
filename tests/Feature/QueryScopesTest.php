<?php

namespace Asantibanez\LaravelEloquentStateMachines\Tests\Feature;

use Asantibanez\LaravelEloquentStateMachines\Tests\TestCase;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesManager;
use Asantibanez\LaravelEloquentStateMachines\Tests\TestModels\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;

class QueryScopesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function can_get_models_with_transition_responsible_model()
    {
        //Arrange
        $salesManager = SalesManager::factory()->create();

        $anotherSalesManager = SalesManager::factory()->create();

        SalesOrder::factory()->create()->status()->transitionTo('approved', [], $salesManager);
        SalesOrder::factory()->create()->status()->transitionTo('approved', [], $salesManager);
        SalesOrder::factory()->create()->status()->transitionTo('approved', [], $anotherSalesManager);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) use ($salesManager) {
                $query->withResponsible($salesManager);
            })
            ->get();

        //Assert
        $this->assertEquals(2, $salesOrders->count());

        $salesOrders->each(function (SalesOrder $salesOrder) use ($salesManager) {
            $this->assertEquals($salesManager->id, $salesOrder->status()->snapshotWhen('approved')->responsible->id);
        });
    }

    #[Test]
    public function can_get_models_with_specific_transition()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved');
        $salesOrder->status()->transitionTo('processed');

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved');

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->withTransition('approved', 'processed');
            })
            ->get();

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    #[Test]
    public function can_get_models_with_specific_transition_to_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved');
        $salesOrder->status()->transitionTo('processed');

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved');

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->transitionedTo('processed');
            })
            ->get();

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    #[Test]
    public function can_get_models_with_an_array_of_transition_to_states()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved');
        $salesOrder->status()->transitionTo('processed');

        $salesOrder2 = SalesOrder::factory()->create();
        $salesOrder2->status()->transitionTo('waiting');
        $salesOrder2->status()->transitionTo('cancelled');

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved');

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->transitionedTo(['processed', 'cancelled']);
            })
            ->get();

        //Assert
        $this->assertEquals(2, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders[0]->id);
        $this->assertEquals($salesOrder2->id, $salesOrders[1]->id);
    }

    #[Test]
    public function can_get_models_with_specific_transition_from_state()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved');
        $salesOrder->status()->transitionTo('processed');

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved');

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->transitionedFrom('approved');
            })
            ->get();

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    #[Test]
    public function can_get_models_with_an_array_of_transition_from_states()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved');
        $salesOrder->status()->transitionTo('processed');

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved');

        $anotherSalesOrder2 = SalesOrder::factory()->create();
        $anotherSalesOrder2->status()->transitionTo('waiting');
        $anotherSalesOrder2->status()->transitionTo('cancelled');

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->transitionedFrom(['approved', 'waiting']);
            })
            ->get();

        //Assert
        $this->assertEquals(2, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders[0]->id);
        $this->assertEquals($anotherSalesOrder2->id, $salesOrders[1]->id);
    }

    #[Test]
    public function can_get_models_with_specific_transition_custom_property()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved', ['comments' => 'Checked']);

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved', ['comments' => 'Needs further revision']);

        //Act
        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->withCustomProperty('comments', 'like', '%Check%');
            })
            ->get();

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }

    #[Test]
    public function can_get_models_using_multiple_state_machines_transitions()
    {
        //Arrange
        $salesOrder = SalesOrder::factory()->create();
        $salesOrder->status()->transitionTo('approved');
        $salesOrder->status()->transitionTo('processed');

        $anotherSalesOrder = SalesOrder::factory()->create();
        $anotherSalesOrder->status()->transitionTo('approved');

        //Act

        $salesOrders = SalesOrder::with([])
            ->whereHasStatus(function ($query) {
                $query->transitionedTo('approved');
            })
            ->whereHasStatus(function ($query) {
                $query->transitionedTo('processed');
            })
            ->get();

        //Assert
        $this->assertEquals(1, $salesOrders->count());

        $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
    }
}
