<?php

namespace Tests\Feature\Notifications;

use App\Mail\NewRfqReceivedMail;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use App\Notifications\Channels\IPPanelChannel;
use App\Notifications\NewRfqReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewRfqReceivedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_queued_on_the_dedicated_rfq_queue(): void
    {
        Queue::fake();

        $rfq = Rfq::factory()->create();

        Notification::send($rfq->recipients(), new NewRfqReceived($rfq));

        // One job per channel; both must stay off the default queue, where a
        // backlog of AI content jobs would delay the lead.
        Queue::assertPushed(SendQueuedNotifications::class, 2);
        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job): bool => $job->queue === 'rfq-notifications',
        );
    }

    public function test_it_goes_to_the_company_owner_and_nobody_else(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $rfq = Rfq::factory()->for(Company::factory()->for($owner))->create();

        Notification::send($rfq->recipients(), new NewRfqReceived($rfq));

        Notification::assertSentTo($owner, NewRfqReceived::class);
        Notification::assertNotSentTo($stranger, NewRfqReceived::class);
    }

    public function test_recipients_is_empty_when_the_company_has_no_owner(): void
    {
        $rfq = Rfq::factory()->create();
        $rfq->setRelation('company', null);

        $this->assertTrue($rfq->recipients()->isEmpty());
    }

    public function test_it_is_delivered_over_mail_and_ippanel(): void
    {
        $rfq = Rfq::factory()->create();

        $this->assertSame(
            ['mail', IPPanelChannel::class],
            (new NewRfqReceived($rfq))->via($rfq->recipients()->first()),
        );
    }

    public function test_the_mail_channel_returns_the_rfq_mailable_addressed_to_the_owner(): void
    {
        $rfq = Rfq::factory()->create();
        $owner = $rfq->recipients()->first();

        $mailable = (new NewRfqReceived($rfq))->toMail($owner);

        $this->assertInstanceOf(NewRfqReceivedMail::class, $mailable);
        $this->assertTrue($mailable->hasTo($owner->email));
        $this->assertSame($rfq->id, $mailable->rfq->id);
    }

    public function test_the_sms_carries_the_registered_pattern_variables(): void
    {
        config([
            'ippanel.rfq.pattern' => 'rfq-pattern',
            'ippanel.rfq.origin_number' => '+983000000',
        ]);

        $rfq = Rfq::factory()->create(['buyer_name' => 'Ada Lovelace', 'locale' => 'ru']);

        $this->assertSame([
            'pattern' => 'rfq-pattern',
            'origin_number' => '+983000000',
            'params' => [
                'buyer_name' => 'Ada Lovelace',
                'locale' => 'ru',
            ],
        ], (new NewRfqReceived($rfq))->toIppanel($rfq->recipients()->first()));
    }
}
