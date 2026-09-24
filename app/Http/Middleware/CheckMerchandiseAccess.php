<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Closure;

class CheckMerchandiseAccess {

    public function handle($request, Closure $next) {
        if (Auth::user()) {
            $services = (!is_null(Auth::user()->services)) ? json_decode(Auth::user()->services) : [];
            if (Auth::user()->access_type == 'vendor' && !(in_array('merchandise', $services))) {
                return redirect('/dashboard');
            }
        }
        return $next($request);
    }

}
