<?php

namespace App\Http\Controllers\Helicopter\Api;

use App\Http\Controllers\Helicopter\HelicopterBaseController;
use App\Constants\HttpStatus as http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Traits\HelicopterTraits as Helicopter;
use App\EmailTemplate;
use Carbon\Carbon;
use DateTime;
use PDF;



class HelicopterApiController extends HelicopterBaseController
{
    use Helicopter;

    /* *********************************************************************************
    * Use getSqlLog function to log the SQL query for debugging purposes in the method.
    * This will help to identify query execution.
    * **********************************************************************************/

    public function health(Request $request)
    {
        try {
            $sql = DB::table('users')->where('id', 1);
            $this->getSqlLog($sql);
            $dbStatus = 'connected';
            return $this->apiResponse([], 'Database is ' . $dbStatus, http::OK, true);
        } catch (\Exception $e) {
            $dbStatus = 'disconnected';
            return $this->apiResponse([],'Database is ' . $dbStatus.' with error: '.$e->getMessage(),http::INTERNAL_SERVER_ERROR, false);
        }
    }

    public function getData(Request $request)
    {
        // Example data retrieval logic
        try{
            DB::beginTransaction();
            $data = DB::table('your_table')->get();
            DB::commit();
            return $this->apiResponse($data, 'Data retrieved successfully', http::OK, true);
        }catch(\Exception $e){
            DB::rollBack();
            return $this->apiResponse([], 'Error retrieving data: ' . $e->getMessage(), http::INTERNAL_SERVER_ERROR, false);
        }
    }
}
