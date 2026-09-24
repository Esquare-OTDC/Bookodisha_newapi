<?php

namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Session;
 
class AutoLogout {
    protected $session;
    protected $timeout = 3600;
     
    public function __construct(Store $session){
        $this->session = $session;
    }
    public function handle($request, Closure $next){
        $isLoggedIn = $request->path() != 'logout';
        if(! session('lastActivityTime'))
            $this->session->put('lastActivityTime', time());
        elseif(time() - $this->session->get('lastActivityTime') > $this->timeout){
            $this->session->forget('lastActivityTime');
//            $cookie = cookie('intend', $isLoggedIn ? url()->current() : 'dashboard');
            Auth::logout();
        }
        $isLoggedIn ? $this->session->put('lastActivityTime', time()) : $this->session->forget('lastActivityTime');
        return $next($request);
    }
}