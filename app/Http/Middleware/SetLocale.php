<?php

namespace App\Http\Middleware;

use App\Support\LocaleContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        LocaleContext::apply(LocaleContext::language(), auth()->user());

        return $next($request);
    }
}
