<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FormBuilderAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('form_builder_unlocked')) {
            return redirect()->route('forms.login')->with('error', 'Access denied.');
        }

        return $next($request);
    }
}
