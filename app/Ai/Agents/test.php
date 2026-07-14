<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('zai')]
#[Model('glm-4.7-flash')]
class test implements Agent, Conversational, HasTools
{
    use Promptable;

    public function providerOptions(string $provider): array
    {
        return [
            'thinking' => ['type' => 'disabled'], // غیرفعال کردن thinking
        ];
    }

    public function instructions(): Stringable|string
    {
        return 'تو هوش مصنوعی دستیار ویراواچ هستی.';
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }
}
