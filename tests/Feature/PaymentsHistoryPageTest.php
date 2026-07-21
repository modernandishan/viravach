<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentsHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_authenticated_users_invoices(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $mine = Invoice::factory()->for($user)->create(['status' => InvoiceStatus::Paid, 'gateway_ref' => 'REF-123']);
        $others = Invoice::factory()->create(['gateway_ref' => 'REF-999']);

        Livewire::actingAs($user)
            ->test('pages::dashboard.payments')
            ->assertSeeText('REF-123')
            ->assertDontSeeText('REF-999');
    }

    public function test_it_shows_an_empty_state_notice(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.payments')
            ->assertSeeText(__('payments.no_invoices_found'));
    }
}
