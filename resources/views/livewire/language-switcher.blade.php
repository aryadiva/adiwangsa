<button
    type="button"
    wire:click="toggle"
    wire:key="language-switcher"
    class="fi-icon-btn relative flex items-center justify-center rounded-lg p-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
    title="{{ strtoupper($locale->value) }}"
>
    <span class="fi-badge text-xs">{{ strtoupper($locale->value) }}</span>
</button>