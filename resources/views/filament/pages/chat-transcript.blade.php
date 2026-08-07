{{--
    Read-only transcript, rendered inside a Filament modal (no send box, no
    actions). isStaffSide mirrors the customer-facing chat's message-item
    partial (resources/views/components/chat-elements/message-item.blade.php):
    one side (AI/support/company) gets a colored bubble, the other (end
    user/guest) a gray one, each on opposite sides of the thread — translated
    here into Tailwind classes since this view renders inside Filament's
    Tailwind-based admin panel rather than the public site's Metronic/Bootstrap
    theme.
--}}
<div class="max-h-[70vh] overflow-y-auto space-y-3 px-1 py-1">
    @forelse($messages as $message)
        @if($message['isSystem'])
            <div class="text-center text-xs text-gray-500 dark:text-gray-400 my-2" dir="auto">
                {{ $message['body'] }}
            </div>
        @else
            <div class="flex {{ $message['isStaffSide'] ? 'justify-end' : 'justify-start' }}">
                <div class="flex flex-col max-w-[75%] {{ $message['isStaffSide'] ? 'items-end' : 'items-start' }}">
                    <span
                        class="text-xs font-bold mb-1 {{ $message['isStaffSide'] ? 'text-blue-700 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}"
                    >
                        {{ $message['sender'] }}
                    </span>

                    <div
                        class="rounded-lg px-3 py-2 {{ $message['isStaffSide'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100' }}"
                    >
                        <div class="text-sm whitespace-pre-wrap" dir="auto">{{ $message['body'] }}</div>
                    </div>

                    @if($message['time'])
                        <span class="text-xs mt-1 text-gray-400 dark:text-gray-500">{{ $message['time'] }}</span>
                    @endif
                </div>
            </div>
        @endif
    @empty
        <div class="text-center text-sm text-gray-500 dark:text-gray-400">
            پیامی برای نمایش وجود ندارد.
        </div>
    @endforelse
</div>
