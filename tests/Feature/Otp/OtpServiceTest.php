<?php

namespace Tests\Feature\Otp;

use App\Models\OtpCode;
use App\Services\Otp\Exceptions\OtpException;
use App\Services\Otp\Exceptions\OtpFailureReason;
use App\Services\Otp\OtpService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ippanel\Client;
use Ippanel\Responses\SendResponse;
use Mockery;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_uses_the_configured_defaults(): void
    {
        config([
            'ippanel.otp.pattern' => 'default-pattern',
            'ippanel.otp.origin_number' => '+983000000',
            'ippanel.otp.digits' => 5,
            'ippanel.otp.pattern_param' => 'verification-code',
        ]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->withArgs(fn (string $pattern, string $sender, string $recipient, array $params) => $pattern === 'default-pattern'
                && $sender === '+983000000'
                && $recipient === '+989121234567'
                && strlen($params['verification-code']) === 5)
            ->andReturn(new SendResponse(['meta' => ['status' => true]]));

        $this->app->instance(Client::class, $client);

        $otp = app(OtpService::class)->send('09121234567');

        $this->assertDatabaseHas('otp_codes', [
            'id' => $otp->id,
            'phone' => '09121234567',
            'purpose' => 'login',
        ]);
    }

    public function test_send_accepts_per_call_overrides_for_reuse_in_other_flows(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->withArgs(fn (string $pattern, string $sender, string $recipient, array $params) => $pattern === 'other-pattern'
                && $sender === '+985550000'
                && strlen($params['custom-param']) === 4)
            ->andReturn(new SendResponse(['meta' => ['status' => true]]));

        $this->app->instance(Client::class, $client);

        app(OtpService::class)->send(
            phone: '09121234567',
            purpose: 'password-reset',
            pattern: 'other-pattern',
            originNumber: '+985550000',
            digits: 4,
            paramKey: 'custom-param',
        );

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '09121234567',
            'purpose' => 'password-reset',
        ]);
    }

    public function test_pattern_param_defaults_to_code(): void
    {
        $this->assertSame('code', config('ippanel.otp.pattern_param'));

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->withArgs(fn (string $pattern, string $sender, string $recipient, array $params) => array_key_exists('code', $params))
            ->andReturn(new SendResponse(['meta' => ['status' => true]]));

        $this->app->instance(Client::class, $client);

        app(OtpService::class)->send('09121234567');
    }

    public function test_domestic_phone_numbers_are_converted_to_e164_for_the_provider(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->withArgs(fn (string $pattern, string $sender, string $recipient, array $params) => $recipient === '+989121234567')
            ->andReturn(new SendResponse(['meta' => ['status' => true]]));

        $this->app->instance(Client::class, $client);

        app(OtpService::class)->send('09121234567');
    }

    public function test_already_international_phone_numbers_are_passed_through_unchanged(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->withArgs(fn (string $pattern, string $sender, string $recipient, array $params) => $recipient === '+989121234567')
            ->andReturn(new SendResponse(['meta' => ['status' => true]]));

        $this->app->instance(Client::class, $client);

        app(OtpService::class)->send('+989121234567');
    }

    public function test_send_throws_when_the_provider_reports_a_failure(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->andReturn(new SendResponse(['meta' => ['status' => false, 'message' => 'boom']]));

        $this->app->instance(Client::class, $client);

        $this->assertOtpException(
            fn () => app(OtpService::class)->send('09121234567'),
            OtpFailureReason::DeliveryFailed,
        );

        $this->assertDatabaseMissing('otp_codes', ['phone' => '09121234567']);
    }

    public function test_resend_is_blocked_during_the_cooldown_window(): void
    {
        config(['ippanel.otp.resend_after' => 90]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->andReturn(new SendResponse(['meta' => ['status' => true]]));

        $this->app->instance(Client::class, $client);

        $service = app(OtpService::class);
        $service->send('09121234567');

        try {
            $service->send('09121234567');
            $this->fail('Expected an OtpException to be thrown.');
        } catch (OtpException $e) {
            $this->assertSame(OtpFailureReason::Cooldown, $e->reason);
            $this->assertGreaterThan(0, $e->retryAfter);
        }
    }

    public function test_verify_succeeds_with_the_correct_code(): void
    {
        $sentCode = $this->sendAndCaptureCode();

        app(OtpService::class)->verify('09121234567', $sentCode);

        $this->assertNotNull(OtpCode::first()->verified_at);
    }

    public function test_verify_fails_with_an_incorrect_code(): void
    {
        $this->sendAndCaptureCode();

        $this->assertOtpException(
            fn () => app(OtpService::class)->verify('09121234567', 'wrong'),
            OtpFailureReason::Invalid,
        );
    }

    public function test_verify_fails_once_the_code_has_expired(): void
    {
        $sentCode = $this->sendAndCaptureCode();

        OtpCode::query()->update(['expires_at' => now()->subMinute()]);

        $this->assertOtpException(
            fn () => app(OtpService::class)->verify('09121234567', $sentCode),
            OtpFailureReason::Expired,
        );
    }

    public function test_verify_locks_out_after_too_many_incorrect_attempts(): void
    {
        config(['ippanel.otp.max_attempts' => 3]);

        $this->sendAndCaptureCode();

        $service = app(OtpService::class);

        foreach (range(1, 3) as $attempt) {
            $this->assertOtpException(
                fn () => $service->verify('09121234567', 'wrong'),
                OtpFailureReason::Invalid,
            );
        }

        $this->assertOtpException(
            fn () => $service->verify('09121234567', 'wrong'),
            OtpFailureReason::TooManyAttempts,
        );
    }

    public function test_verify_fails_when_no_code_was_ever_requested(): void
    {
        $this->assertOtpException(
            fn () => app(OtpService::class)->verify('09121234567', '123456'),
            OtpFailureReason::NotFound,
        );
    }

    private function sendAndCaptureCode(string $phone = '09121234567'): string
    {
        $sentCode = null;

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('sendPattern')
            ->once()
            ->andReturnUsing(function (string $pattern, string $sender, string $recipient, array $params) use (&$sentCode) {
                $sentCode = $params[config('ippanel.otp.pattern_param')];

                return new SendResponse(['meta' => ['status' => true]]);
            });

        $this->app->instance(Client::class, $client);

        app(OtpService::class)->send($phone);

        return $sentCode;
    }

    private function assertOtpException(Closure $callback, OtpFailureReason $expectedReason): void
    {
        try {
            $callback();
            $this->fail('Expected an OtpException to be thrown.');
        } catch (OtpException $e) {
            $this->assertSame($expectedReason, $e->reason);
        }
    }
}
