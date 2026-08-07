<x-filament-panels::page>
    <div class="flex gap-2">
        <x-filament::button
            color="{{ $activeTab === 'ai' ? 'primary' : 'gray' }}"
            wire:click="setTab('ai')"
        >
            گفتگوهای هوش مصنوعی
        </x-filament::button>

        <x-filament::button
            color="{{ $activeTab === 'human' ? 'primary' : 'gray' }}"
            wire:click="setTab('human')"
        >
            گفتگوهای انسانی
        </x-filament::button>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
