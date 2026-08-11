<?php

namespace App\Support;

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Resolves and applies the active UI locale. Persisted per user so the
 * choice survives logout/login.
 */
final class LocaleContext
{
    public static function language(?User $user = null): Locale
    {
        $user ??= auth()->user();

        return Locale::tryFrom($user?->locale ?? '') ?? Locale::English;
    }

    /**
     * Applies the given locale for the current request and, when a user is
     * authenticated, persists the choice to their account.
     */
    public static function apply(Locale $locale, ?User $user = null): void
    {
        app()->setLocale($locale->value);
        Carbon::setLocale($locale->value);

        $user ??= auth()->user();

        if ($user !== null && $user->locale !== $locale->value) {
            $user->update(['locale' => $locale->value]);
        }
    }
}
