<?php

namespace App\Http\Controllers\Helicopter;

use App\Http\Controllers\Controller;
use App\Traits\HelicopterTraits as Helicopter;

class HelicopterBaseController extends Controller
{
    use Helicopter;

    public function apiResponse($data = [], string $message = '', int $status = 200, bool $success = true)
    {
        return response()->json([
            'status' => $success,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}
