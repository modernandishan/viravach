<?php

namespace Tests\Feature\Auth;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ippanel\Client;
use Ippanel\Responses\SendResponse;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SecureLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_secure_login_page(): void
    {
        $this->get(route('auth.secure-login'))->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_secure_login_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('auth.secure-login'))
            ->assertRedirect();
    }

    public function test_requesting_a_code_for_an_unknown_phone_number_fails(): void
    {
        Livewire::test('pages::auth.secure-login')
            ->set('phone', '09120000000')
            ->call('sendCode')
            ->assertHasErrors(['phone'])
            ->assertSet('step', 'phone');
    }

    public function test_user_can_request_and_verify_a_code_to_sign_in(): void
    {
        $user = User::factory()->create();
        $sentCode = $this->fakeSuccessfulOtpDelivery();

        $component = Livewire::test('pages::auth.secure-login')
            ->set('phone', $user->phone)
            ->call('sendCode')
            ->assertHasNoErrors()
            ->assertSet('step', 'code');

        foreach (str_split($sentCode()) as $index => $digit) {
            $component->set("code.{$index}", $digit);
        }

        $component->call('verifyCode')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_verifying_an_incorrect_code_fails_and_keeps_the_user_a_guest(): void
    {
        $user = User::factory()->create();
        $sentCode = $this->fakeSuccessfulOtpDelivery();

        $digits = (int) config('ippanel.otp.digits');
        $wrongCode = str_repeat('9', $digits) === $sentCode() ? str_repeat('1', $digits) : str_repeat('9', $digits);

        $component = Livewire::test('pages::auth.secure-login')
            ->set('phone', $user->phone)
            ->call('sendCode')
            ->assertSet('step', 'code');

        foreach (str_split($wrongCode) as $index => $digit) {
            $component->set("code.{$index}", $digit);
        }

        $component->call('verifyCode')->assertHasErrors(['code']);

        $this->assertGuest();
    }

    public function test_resending_a_code_before_the_cooldown_elapses_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->fakeSuccessfulOtpDelivery(times: 1);

        Livewire::test('pages::auth.secure-login')
            ->set('phone', $user->phone)
            ->call('sendCode')
            ->assertSet('step', 'code')
            ->call('resendCode')
            ->assertHasErrors(['resend']);

        $this->assertSame(1, OtpCode::where('phone', $user->phone)->count());
    }

    /**
     * Mocks the IPPanel client to succeed and returns a closure exposing the
     * generated code once the component has requested it.
     */
    private function fakeSuccessfulOtpDelivery(int $times = 1): \Closure
    {
        $sentCode = null;

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->times($times)
            ->andReturnUsing(function (string $pattern, string $sender, string $recipient, array $params) use (&$sentCode) {
                $sentCode = $params[config('ippanel.otp.pattern_param')];

                return new SendResponse(['meta' => ['status' => true]]);
            });

        $this->app->instance(Client::class, $client);

        return function () use (&$sentCode) {
            return $sentCode;
        };
    }
}
