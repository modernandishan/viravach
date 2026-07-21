<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Traits\Messageable;

#[Fillable(['token', 'user_id'])]
class Guest extends Model
{
    use Messageable;

    public function getParticipantDetailsAttribute(): array
    {
        return ['name' => __('roles.unknown'), 'type' => 'guest'];
    }
}
