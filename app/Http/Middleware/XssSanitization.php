<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Closure;

class XssSanitization {

    public function handle(Request $request, Closure $next) {
        
        $input = $request->all();
        array_walk_recursive($input, function(&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);
        
        return $next($request);
    }

}
