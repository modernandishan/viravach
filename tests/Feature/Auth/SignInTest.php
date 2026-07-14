<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SignInTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_profile_when_a_user_is_created(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_can_view_the_sign_in_page(): void
    {
        $this->get(route('auth.sign-in'))->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_sign_in_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('auth.sign-in'))
            ->assertRedirect();
    }

    public function test_guest_is_redirected_away_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('auth.sign-in'));
    }

    public function test_user_can_sign_in_with_email(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        Livewire::test('pages::auth.sign-in')
            ->set('login', $user->email)
            ->set('password', 'secret-password')
            ->call('authenticate')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_sign_in_with_phone(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        Livewire::test('pages::auth.sign-in')
            ->set('login', $user->phone)
            ->set('password', 'secret-password')
            ->call('authenticate')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_super_admin_is_redirected_to_the_admin_panel(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);
        $user->assignRole('super_admin');

        Livewire::test('pages::auth.sign-in')
            ->set('login', $user->email)
            ->set('password', 'secret-password')
            ->call('authenticate')
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_shareholder_is_redirected_to_the_admin_panel(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);
        $user->assignRole('shareholder');

        Livewire::test('pages::auth.sign-in')
            ->set('login', $user->email)
            ->set('password', 'secret-password')
            ->call('authenticate')
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_sign_in_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        Livewire::test('pages::auth.sign-in')
            ->set('login', $user->email)
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        $this->assertGuest();
    }

    public function test_sign_in_requires_login_and_password(): void
    {
        Livewire::test('pages::auth.sign-in')
            ->set('login', '')
            ->set('password', '')
            ->call('authenticate')
            ->assertHasErrors(['login' => 'required', 'password' => 'required']);
    }
}
