<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Ippanel\Client;
use RuntimeException;

/**
 * Delivers pattern-based SMS through IPPanel.
 *
 * Reuses the same singleton Ippanel\Client that App\Services\Otp\OtpService
 * resolves (bound by the SDK's own service provider from config('ippanel.*')),
 * so there is exactly one HTTP client and one set of credentials in the app.
 *
 * A notification opts in by implementing toIppanel(), which must return the
 * pattern code, the origin number and the pattern's variables:
 *
 *     public function toIppanel(object $notifiable): array
 *     {
 *         return [
 *             'pattern' => config('ippanel.rfq.pattern'),
 *             'origin_number' => config('ippanel.rfq.origin_number'),
 *             'params' => ['buyer_name' => '…', 'locale' => 'en'],
 *         ];
 *     }
 */
class IPPanelChannel
{
    public function __construct(private readonly Client $client) {}

    /**
     * @throws RuntimeException when IPPanel rejects the message
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('ippanel', $notification);

        if (blank($phone)) {
            return;
        }

        /** @var array{pattern: string, origin_number: string, params: array<string, string>} $message */
        $message = $notification->toIppanel($notifiable);

        if (blank($message['pattern'])) {
            return;
        }

        $response = $this->client->sendPattern(
            $message['pattern'],
            $message['origin_number'],
            $this->toE164($phone),
            $message['params'],
        );

        // Thrown rather than logged so the queued notification is retried and
        // ends up in failed_jobs if IPPanel stays unreachable.
        if (! $response->isSuccessful()) {
            throw new RuntimeException('IPPanel delivery failed: '.$response->getMessage());
        }
    }

    /**
     * IPPanel expects recipients in E.164 format (e.g. +989121234567), while
     * phone numbers are stored locally in the domestic 09xxxxxxxxx format —
     * same normalisation OtpService applies before sending.
     */
    protected function toE164(string $phone): string
    {
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+98'.substr($phone, 1);
        }

        return $phone;
    }
}
