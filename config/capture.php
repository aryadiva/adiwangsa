<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Live-Capture Integrity (PRD v3 §6.4 / §8.1)
    |--------------------------------------------------------------------------
    | Camera-only capture is enforced client-side (getUserMedia / <input
    | capture>) and treated as a soft guarantee. Server-side, every capture
    | must embed a timestamp in its filename at shutter time; this window
    | bounds how far in the past (or future, for clock skew) that stamp may
    | sit relative to the server clock.
    */

    'max_age_seconds' => (int) env('LIVE_CAPTURE_MAX_AGE_SECONDS', 900),

    'future_skew_seconds' => (int) env('LIVE_CAPTURE_FUTURE_SKEW_SECONDS', 120),
];
