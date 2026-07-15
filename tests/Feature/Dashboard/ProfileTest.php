<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Ippanel\Client;
use Ippanel\Responses\SendResponse;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_profile_page(): void
    {
        $this->get(route('profile'))->assertRedirect();
    }

    public function test_authenticated_user_can_view_the_profile_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile'))
            ->assertOk();
    }

    public function test_user_can_update_profile_and_user_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->set('name', 'Updated Name')
            ->set('family', 'Updated Family')
            ->set('username', 'updated-username')
            ->set('nationalCode', '1112223334')
            ->set('city', 'Isfahan')
            ->set('skills', 'Laravel, PHP,  Vue ')
            ->set('socialInstagram', 'https://instagram.com/example')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $user = $user->fresh();

        $this->assertSame('Updated Name', $user->getTranslation('name', app()->getLocale(), false));
        $this->assertSame('Updated Family', $user->getTranslation('family', app()->getLocale(), false));
        $this->assertSame('updated-username', $user->profile->username);
        $this->assertSame('1112223334', $user->profile->national_code);
        $this->assertSame('Isfahan', $user->profile->city);
        $this->assertSame(['Laravel', 'PHP', 'Vue'], $user->profile->skills);
        $this->assertSame('https://instagram.com/example', $user->profile->social_links['instagram']);
    }

    public function test_birth_date_is_entered_as_jalali_and_stored_as_gregorian(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->set('birthDate', '1370/05/02')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('1991-07-24', $user->fresh()->profile->birth_date->format('Y-m-d'));
    }

    public function test_birth_date_is_displayed_back_as_jalali_on_mount(): void
    {
        $user = User::factory()->create();
        $user->profile()->update(['birth_date' => '1991-07-24']);

        Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->assertSet('birthDate', '1370/05/02');
    }

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->set('avatar', UploadedFile::fake()->image('avatar.jpg', 200, 200))
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->hasMedia('avatar'));
    }

    public function test_user_can_change_email_via_otp_flow(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $sentCode = $this->fakeSuccessfulOtpDelivery();

        $component = Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->set('newEmail', 'new@example.com')
            ->call('requestEmailChange')
            ->assertHasNoErrors()
            ->assertSet('securityStep', 'code');

        foreach (str_split($sentCode()) as $index => $digit) {
            $component->set("securityCode.{$index}", $digit);
        }

        $component->call('verifySecurityCode')->assertHasNoErrors();

        $user = $user->fresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_can_change_password_via_otp_flow(): void
    {
        $user = User::factory()->create();
        $sentCode = $this->fakeSuccessfulOtpDelivery();

        $component = Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->set('newPassword', 'new-secret-password')
            ->set('newPassword_confirmation', 'new-secret-password')
            ->call('requestPasswordChange')
            ->assertHasNoErrors()
            ->assertSet('securityStep', 'code');

        foreach (str_split($sentCode()) as $index => $digit) {
            $component->set("securityCode.{$index}", $digit);
        }

        $component->call('verifySecurityCode')->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_otp_requests_for_security_actions_are_rate_limited(): void
    {
        $user = User::factory()->create();
        $this->fakeSuccessfulOtpDelivery(times: 3);

        $component = Livewire::actingAs($user)->test('pages::dashboard.profile');

        // OtpService itself also throttles resends per (phone, purpose), so
        // travel past its resend_after cooldown between attempts — leaving
        // our own 10-minute RateLimiter window as the only remaining gate.
        foreach (range(1, 3) as $i) {
            $this->travel(100)->seconds();

            $component->set('newEmail', "candidate{$i}@example.com")
                ->call('requestEmailChange')
                ->assertHasNoErrors();
        }

        $component->set('newEmail', 'candidate4@example.com')
            ->call('requestEmailChange')
            ->assertHasErrors(['securityOtp']);
    }

    public function test_user_can_deactivate_account_via_otp_flow_without_being_logged_out(): void
    {
        $user = User::factory()->create();
        $sentCode = $this->fakeSuccessfulOtpDelivery();

        $component = Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->set('confirmDeactivation', true)
            ->call('requestDeactivation')
            ->assertHasNoErrors()
            ->assertSet('deactivateStep', true);

        foreach (str_split($sentCode()) as $index => $digit) {
            $component->set("deactivateCode.{$index}", $digit);
        }

        $component->call('verifyDeactivationCode')->assertHasNoErrors();

        $this->assertNotNull($user->fresh()->deactivated_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_reactivate_a_deactivated_account_without_otp(): void
    {
        $user = User::factory()->create(['deactivated_at' => now()]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.profile')
            ->call('reactivateAccount')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->deactivated_at);
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
