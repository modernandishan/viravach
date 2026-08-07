{{--
    Shared message bubble, reused by the header chat drawer, the full-page
    chat client, and the support agent inbox. Expects $msg (see
    presentMessage() shape: id, body, senderName, senderType, senderAvatar,
    isOwn, time, type) and $translations (keyed by message id). The enclosing
    Livewire component must define toggleTranslation($id). senderType and
    senderAvatar are optional — components that don't set them (e.g. the
    support inbox) simply never suppress the translate link / show an avatar.

    Bubble side follows the current page direction (the layouts set dir + the
    matching LTR/RTL bundle from LaravelLocalization::getCurrentLocaleDirection()):
    own messages hug the inline-start edge (left in LTR, right in RTL) and
    everyone else's the inline-end edge, so flex start/end — not hardcoded
    left/right — is what keeps both directions correct.
--}}
@if($msg['type'] === 'system')
    <!--begin::System notice-->
    <div class="text-center text-muted fs-8 my-4" wire:key="chat-message-{{ $msg['id'] }}">
        {{ $msg['body'] }}
    </div>
    <!--end::System notice-->
@else
    <!--begin::پیام-->
    <div class="d-flex {{ $msg['isOwn'] ? 'justify-content-start' : 'justify-content-end' }} mb-5" wire:key="chat-message-{{ $msg['id'] }}">
        <!--begin::Wrapper-->
        {{-- mw-75 caps the bubble at ~75% of the row so the alignment side stays obvious even for short messages. --}}
        <div class="d-flex flex-column mw-75 {{ $msg['isOwn'] ? 'align-items-start' : 'align-items-end' }}">
            <!--begin::user-->
            <div class="d-flex align-items-center mb-1">
                <span class="fs-7 fw-bold text-gray-900">{{ $msg['senderName'] }}</span>
            </div>
            <!--end::user-->
            <!--begin::Text-->
            <div class="px-4 py-2 rounded {{ $msg['isOwn'] ? 'bg-primary text-white' : 'bg-gray-200 text-gray-900' }} fw-semibold">
                <div style="white-space: pre-wrap;" dir="auto">{{ trim($msg['body']) }}</div>

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

                @if($msg['time'])
                    <div class="{{ $msg['isOwn'] ? 'text-white opacity-75' : 'text-muted' }} fs-9 fw-normal mt-1 text-end">{{ $msg['time'] }}</div>
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
        @if(! $msg['isOwn'] && ($msg['senderAvatar'] ?? null))
            {{-- Non-own messages sit at the inline-end edge, so their avatar goes on the outer (end) side. --}}
            <div class="symbol symbol-35px symbol-circle ms-3 mt-6">
                <img src="{{ $msg['senderAvatar'] }}" alt="{{ $msg['senderName'] }}"/>
            </div>
        @endif
    </div>
    <!--end::پیام-->
@endif
