{{--
    Sitewide flash / validation notifications, rendered as SweetAlert2 toasts.

    WHY A PARTIAL AND NOT A LAYOUT-ONLY BLOCK
    -----------------------------------------
    A Livewire update request never re-renders the layout, so a layout-only
    include would silently drop every message flashed by a wire:click that does
    not redirect (⚡settings, ⚡profile, ⚡subscriptions all do exactly that).
    Include this partial:

      * once in every layout      — covers full page loads and redirect flashes;
      * once inside the root <div> of any Livewire component that flashes
        WITHOUT redirecting — covers the same-round-trip case.

    Including it in both places is safe: keys are consumed (forgotten) as they
    are read, and the component renders before the layout, so a message is
    shown exactly once.

    CONVENTION
    ----------
    Flash with any of these and it shows up automatically, no markup needed:

        session()->flash('flash_success', $message);   // success toast
        session()->flash('flash_error', $message);     // error toast
        session()->flash('flash_warning', $message);   // warning toast
        session()->flash('flash_info', $message);      // info toast

    Suffix matching also works, which is what keeps the pre-existing keys
    ('company-status', 'profile-status', 'settings-status',
    'subscription-status') working untouched: anything ending in -status /
    _status is a success toast, -error / _error an error toast, and so on.

    Values may be a string or an array of strings.

    OPTIONAL VARIABLES
    ------------------
    $includeErrorBag (bool, default false) — also toast the whole $errors bag.
    Layouts pass true: there the bag can only have been filled by a classic
    redirect-with-errors. Livewire components must NOT pass it, or every
    field-level @error message would be duplicated as a toast.

    $errorKeys (array, default []) — toast only these keys of the $errors bag.
    This is for form-level validation keys that have no input of their own and
    used to be rendered as a standalone alert, e.g.
    @include('partials.flash-alerts', ['errorKeys' => ['cooldown']]).
--}}
@php
    $flashIncludeErrorBag = $includeErrorBag ?? false;
    $flashErrorKeys = $errorKeys ?? [];

    /**
     * Map a flashed session key to a SweetAlert2 icon, or null when the key
     * carries data rather than a message.
     *
     * @return 'success'|'error'|'warning'|'info'|null
     */
    $flashIconFor = static function (string $key): ?string {
        $key = strtolower($key);

        $exact = [
            'flash_success' => 'success',
            'flash_error' => 'error',
            'flash_warning' => 'warning',
            'flash_info' => 'info',
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            'status' => 'success',
            'message' => 'info',
        ];

        if (isset($exact[$key])) {
            return $exact[$key];
        }

        // Suffix conventions, e.g. 'settings-status' or 'wordpress_error'.
        $suffixes = [
            'status' => 'success',
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
        ];

        foreach ($suffixes as $suffix => $icon) {
            if (str_ends_with($key, '-'.$suffix) || str_ends_with($key, '_'.$suffix)) {
                return $icon;
            }
        }

        return null;
    };

    // Only genuinely flashed keys are considered — reading session()->all()
    // would also pick up persistent data such as 'pending_invoice_id'.
    $flashKeys = array_unique(array_merge(
        (array) session()->get('_flash.old', []),
        (array) session()->get('_flash.new', []),
    ));

    $flashMessages = [];

    foreach ($flashKeys as $flashKey) {
        $icon = $flashIconFor((string) $flashKey);

        if ($icon === null) {
            continue;
        }

        $value = session()->get($flashKey);

        // Consume it: the message must not be shown again by the layout include
        // further down the same render, nor survive into the next request.
        session()->forget($flashKey);

        foreach (Arr::wrap($value) as $line) {
            if (is_string($line) && trim($line) !== '') {
                $flashMessages[] = ['icon' => $icon, 'title' => $line];
            }
        }
    }

    if (isset($errors)) {
        if ($flashIncludeErrorBag) {
            foreach ($errors->all() as $line) {
                $flashMessages[] = ['icon' => 'error', 'title' => $line];
            }
        } else {
            foreach ($flashErrorKeys as $errorKey) {
                foreach ($errors->get($errorKey) as $line) {
                    $flashMessages[] = ['icon' => 'error', 'title' => $line];
                }
            }
        }
    }
@endphp

@if ($flashMessages !== [])
    {{-- The nonce key forces Livewire's morph to insert a brand-new node on
         every render that carries messages, which is what makes Alpine run
         x-init again; $el.remove() then clears it so a repeat of the very same
         message still toasts. The queue + drain indirection exists because
         Alpine (booted by Livewire's classic script tag) can start before this
         page's deferred module bundle has defined the drain function. --}}
    <div wire:key="flash-alerts-{{ Str::random(8) }}"
         style="display: none"
         x-data
         x-init="(window.viravachFlashQueue ??= []).push(...{{ Js::from($flashMessages) }});
                 window.viravachFlashDrain?.();
                 $nextTick(() => $el.remove())"></div>
@endif
