<?php

namespace App\Events\Ai;

use App\Models\CompanyContent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Announces AI content generation progress for one company so the
 * dashboard card updates without a refresh. Broadcast on a private
 * company channel following the SAME naming and authorization convention
 * as the chat system's company channel (routes/channels.php): the channel
 * is named after the company and only its owner may subscribe.
 */
class ContentGenerationProgressed implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * If dispatched inside a database transaction (a step's save), the
     * broadcast waits for the commit — never announcing phantom progress.
     */
    public $afterCommit = true;

    public int $companyId;

    public string $status;

    public int $step;

    public ?string $failureReason;

    /**
     * Resolved up-front so the queued broadcast job never needs the model.
     */
    protected string $channelName;

    public function __construct(CompanyContent $content)
    {
        $this->companyId = (int) $content->company_id;
        $this->status = $content->status->value;
        $this->step = (int) $content->step;
        $this->failureReason = $content->failure_reason;
        $this->channelName = static::channelNameFor($this->companyId);
    }

    /**
     * Same kebab-prefix.{id} convention as the chat system's
     * chat-participant.company.{id} channel.
     */
    public static function channelNameFor(int $companyId): string
    {
        return 'ai-content.company.'.$companyId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->channelName);
    }

    /**
     * @return array{company_id: int, status: string, step: int, failure_reason: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'status' => $this->status,
            'step' => $this->step,
            'failure_reason' => $this->failureReason,
        ];
    }
}
