<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolves the locale a notification should be rendered in — the
 * recipient's saved `users.locale`, falling back to the app locale.
 * Keeps internal notifications (mail + bell database payloads) aligned
 * with the per-user language switcher.
 */
final class NotifiableLocale
{
    public static function of(object $notifiable): string
    {
        $locale = $notifiable instanceof User ? $notifiable->locale : null;

        return $locale !== null && $locale !== ''
            ? $locale
            : app()->getLocale();
    }
}
