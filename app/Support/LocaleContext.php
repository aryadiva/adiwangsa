<?php

namespace App\Support;

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Resolves and applies the active UI locale. Persisted per user so the
 * choice survives logout/login; for guests (pre-login) the choice lives
 * in the session so the login-page switcher works.
 */
final class LocaleContext
{
    public const GUEST_SESSION_KEY = 'locale';

    public static function language(?User $user = null): Locale
    {
        if ($user === null) {
            $user = auth()->user();
        }

        if ($user === null) {
            return Locale::tryFrom(session(self::GUEST_SESSION_KEY, '')) ?? Locale::English;
        }

        return Locale::tryFrom($user->locale ?? '') ?? Locale::English;
    }

    /**
     * Applies the given locale for the current request and, when a user is
     * authenticated, persists the choice to their account; otherwise to the
     * session.
     */
    public static function apply(Locale $locale, ?User $user = null): void
    {
        app()->setLocale($locale->value);
        Carbon::setLocale($locale->value);

        if ($user === null) {
            $user = auth()->user();
        }

        if ($user === null) {
            session([self::GUEST_SESSION_KEY => $locale->value]);

            return;
        }

        if ($user->locale !== $locale->value) {
            $user->update(['locale' => $locale->value]);
        }
    }
}
