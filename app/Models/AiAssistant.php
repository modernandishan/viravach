<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Traits\Messageable;

class AiAssistant extends Model
{
    use Messageable;

    public function getParticipantDetailsAttribute(): array
    {
        return ['name' => $this->name, 'type' => 'ai'];
    }
}
