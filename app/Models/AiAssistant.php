<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Traits\Messageable;

class AiAssistant extends Model
{
    use Messageable;

    /**
     * Resolved at render time (not stored on messages), so the bot's display
     * name follows the current request's locale via the lang key.
     */
    public function getParticipantDetailsAttribute(): array
    {
        return [
            'name' => __('chat.virabot_name'),
            'type' => 'ai',
            // Placeholder avatar from the bundled theme assets — replace with
            // a real ViraBot avatar once one is designed/uploaded.
            'avatar_url' => asset('theme/1/media/avatars/blank.png'),
        ];
    }
}
