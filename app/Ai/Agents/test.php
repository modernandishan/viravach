<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('zai')]
#[Model('glm-4.7-flash')]
#[Temperature(0.3)]
class test implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function providerOptions(string $provider): array
    {
        return [
            'thinking' => ['type' => 'disabled'],
        ];
    }

    public function instructions(): Stringable|string
    {
        return 'تو دستیار هوش مصنوعی پلتفرم ویراواچ هستی. کوتاه و دقیق پاسخ بده (حداکثر ۳-۴ جمله). فقط درباره‌ی خدمات ویراواچ صحبت کن. اگر سوال نیاز به پیگیری انسانی دارد، کاربر را به پشتیبان ارجاع بده.';
    }

    /*public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }*/
}
