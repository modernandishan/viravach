{{--
    Shared message bubble, reused by the header chat drawer, the full-page
    chat client, and the support agent inbox. Expects $msg (see
    presentMessage() shape: id, body, senderName, senderType, isOwn, time,
    type) and $translations (keyed by message id). The enclosing Livewire
    component must define toggleTranslation($id). senderType is optional —
    components that don't set it (e.g. the support inbox) simply never
    suppress the translate link based on it.
--}}
@if($msg['type'] === 'system')
    <!--begin::System notice-->
    <div class="text-center text-muted fs-8 my-4" wire:key="chat-message-{{ $msg['id'] }}">
        {{ $msg['body'] }}
    </div>
    <!--end::System notice-->
@else
    <!--begin::پیام-->
    <div class="d-flex {{ $msg['isOwn'] ? 'justify-content-end' : 'justify-content-start' }} mb-10" wire:key="chat-message-{{ $msg['id'] }}">
        <!--begin::Wrapper-->
        <div class="d-flex flex-column {{ $msg['isOwn'] ? 'align-items-end' : 'align-items-start' }}">
            <!--begin::user-->
            <div class="d-flex align-items-center mb-2">
                <span class="fs-7 fw-bold text-gray-900 {{ $msg['isOwn'] ? 'ms-1' : 'me-1' }}">{{ $msg['senderName'] }}</span>
                @if($msg['time'])
                    <span class="text-muted fs-8">{{ $msg['time'] }}</span>
                @endif
            </div>
            <!--end::user-->
            <!--begin::Text-->
            <div class="p-5 rounded {{ $msg['isOwn'] ? 'bg-light-primary text-end' : 'bg-light-info text-start' }} text-gray-900 fw-semibold mw-lg-400px" style="white-space: pre-wrap;">
                {{ $msg['body'] }}

                @if($translations[$msg['id']]['visible'] ?? false)
                    <!--begin::Translation-->
                    <div class="text-muted fs-8 fw-normal mt-2 pt-2 border-top border-gray-300 border-opacity-50" style="white-space: pre-wrap;" dir="auto">
                        @if($translations[$msg['id']]['error'] ?? null)
                            <span class="text-danger">{{ $translations[$msg['id']]['error'] }}</span>
                        @else
                            {{ $translations[$msg['id']]['text'] ?? '' }}
                        @endif
                    </div>
                    <!--end::Translation-->
                @endif
            </div>
            <!--end::Text-->

            {{-- ViraBot replies are already in the user's language, so translating them is wrong and wasteful. --}}
            @unless($msg['isOwn'] || ($msg['senderType'] ?? null) === 'ai')
                <!--begin::Translate-->
                <button type="button" class="btn btn-link p-0 fs-9 text-muted mt-1" wire:click="toggleTranslation({{ $msg['id'] }})" wire:target="toggleTranslation({{ $msg['id'] }})" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="toggleTranslation({{ $msg['id'] }})">
                        {{ ($translations[$msg['id']]['visible'] ?? false) ? __('chat.hide_translation') : __('chat.translate_link') }}
                    </span>
                    <span wire:loading wire:target="toggleTranslation({{ $msg['id'] }})" class="spinner-border spinner-border-sm align-middle"></span>
                </button>
                <!--end::Translate-->
            @endunless
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::پیام-->
@endif
