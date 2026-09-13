<?php

namespace App\Notifications;

use App\Mail\NewRfqReceivedMail;
use App\Models\Rfq;
use App\Notifications\Channels\IPPanelChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Tells a company owner that a quote request just landed.
 *
 * Dual-channel on purpose: the SMS is the "go look now" nudge (short, one
 * pattern), the email carries the buyer's actual details so the owner can
 * answer without opening the dashboard first. The two speak different
 * languages by design: the email renders in the buyer's locale (see
 * App\Mail\NewRfqReceivedMail), while the SMS only *names* that locale to
 * the owner in its own pattern variable.
 *
 * Runs on its own 'rfq-notifications' queue rather than the default one so a
 * backlog of AI content jobs can never delay a lead — these are the only
 * messages in the app where minutes matter to the customer.
 */
#[QueueAttribute('rfq-notifications')]
class NewRfqReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Rfq $rfq)
    {
        // Notification-level locale, which is deliberately NOT the email's:
        // NewRfqReceivedMail pins its own render locale to $rfq->locale and
        // so overrides this for every part of the mail. What is left under
        // this pin is the SMS path, whose words come from a pattern
        // registered in the IPPanel dashboard rather than from a lang file —
        // the site default keeps any __() added there later off the buyer's
        // language, which the owner has no reason to read.
        $this->locale = LaravelLocalization::getDefaultLocale();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', IPPanelChannel::class];
    }

    public function toMail(object $notifiable): NewRfqReceivedMail
    {
        return (new NewRfqReceivedMail($this->rfq))->to($notifiable->email);
    }

    /**
     * Variables of the RFQ pattern registered in the IPPanel dashboard.
     *
     * @return array{pattern: string, origin_number: string, params: array<string, string>}
     */
    public function toIppanel(object $notifiable): array
    {
        return [
            'pattern' => (string) config('ippanel.rfq.pattern'),
            'origin_number' => (string) config('ippanel.rfq.origin_number'),
            'params' => [
                'buyer_name' => $this->rfq->buyer_name,
                'locale' => $this->rfq->locale,
            ],
        ];
    }
}
