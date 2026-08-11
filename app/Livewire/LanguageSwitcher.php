<?php

namespace App\Livewire;

use App\Enums\Locale;
use App\Support\LocaleContext;
use Illuminate\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public Locale $locale;

    public function mount(): void
    {
        $this->locale = LocaleContext::language();
    }

    public function toggle(): void
    {
        $this->locale = $this->locale === Locale::English ? Locale::Indonesian : Locale::English;
        LocaleContext::apply($this->locale);

        $this->js('window.location.reload()');
    }

    public function render(): View
    {
        return view('livewire.language-switcher');
    }
}
