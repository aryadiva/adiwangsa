<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\InvokableRule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Server-side secondary check on embedded capture metadata (PRD v3 §8.1).
 * The client-side camera-only component is a soft guarantee; this rule
 * independently verifies the presence and recency of the capture timestamp
 * the live-capture component stamps into the upload's filename
 * (`capture-<ISO 8601>.jpg`) at shutter time.
 */
class LiveCapture implements InvokableRule
{
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        $this->validate($attribute, $value, $fail);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Empty is the `required` rule's concern.
        if ($value === null || $value === '') {
            return;
        }

        $timestamp = static::embeddedTimestamp($value);

        if ($timestamp === null) {
            // Already-stored final path (edit form filled from DB, no
            // recapture) passed this check when originally captured.
            if (is_string($value) && ! str_starts_with($value, 'livewire-file:')) {
                return;
            }

            $fail('The capture timestamp is missing. Please capture the photo live with the camera.');

            return;
        }

        $maxAge = (int) config('capture.max_age_seconds', 900);
        $futureSkew = (int) config('capture.future_skew_seconds', 120);

        if ($timestamp->gt(now()->addSeconds($futureSkew))) {
            $fail('The capture timestamp is in the future. Please capture the photo live with the camera.');

            return;
        }

        if ($timestamp->lt(now()->subSeconds($maxAge))) {
            $fail('This capture is too old. Please capture the photo live with the camera.');
        }
    }

    /**
     * Extracts the client-stamped shutter timestamp from a live capture's
     * filename, regardless of whether the state is the live TemporaryUploadedFile
     * or its serialized `livewire-file:` token.
     */
    public static function embeddedTimestamp(mixed $state): ?Carbon
    {
        $filename = static::filenameFrom($state);

        if ($filename === null || preg_match('/^capture-(.+)\.(jpe?g|png)$/i', $filename, $matches) !== 1) {
            return null;
        }

        try {
            return Carbon::parse($matches[1]);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function filenameFrom(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->getClientOriginalName();
        }

        if (is_string($state)) {
            if (str_starts_with($state, 'livewire-file:')) {
                try {
                    return TemporaryUploadedFile::createFromLivewire(
                        substr($state, strlen('livewire-file:'))
                    )->getClientOriginalName();
                } catch (\Throwable) {
                    return null;
                }
            }

            return basename($state);
        }

        return null;
    }
}
