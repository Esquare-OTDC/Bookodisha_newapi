<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;

class CheckVendorAccess {

    public function handle($request, Closure $next) {
        if (Auth::user()) {
            if (Auth::user()->access_type != 'vendor') {
                return redirect('/dashboard');
            }
        }
        return $next($request);
    }

}
