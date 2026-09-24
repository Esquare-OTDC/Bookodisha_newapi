<?php

namespace App\Helper;

class Helper
{
    public function adminAccess() {
        if (Auth::user()->role != 1) {
            return Redirect::to('dashboard');
        }
    }
}