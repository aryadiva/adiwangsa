<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        A temporary password was assigned to your account. Please set a new password before continuing.
    </p>

    {{ $this->form }}

    <x-filament::button wire:click="submit">
        Update Password
    </x-filament::button>
</x-filament-panels::page>
