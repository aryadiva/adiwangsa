<?php

namespace App\Http\Middleware;

use App\Filament\Client\Pages\ChangePassword;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $target = ChangePassword::getUrl();
            $targetPath = ltrim((string) parse_url($target, PHP_URL_PATH), '/');

            if ($request->path() !== $targetPath) {
                return redirect()->to($target);
            }
        }

        return $next($request);
    }
}
