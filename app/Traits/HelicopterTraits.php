<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HelicopterTraits
{
    protected function logError($message, $exception)
    {
        Log::error($message . ': ' . $exception->getMessage());
    }

    protected function logInfo($message)
    {
        Log::info($message);
    }

    // add log for array data
    protected function logArray($message, $array)
    {
        Log::info($message . ': ' . json_encode($array));
    }

    // get sql query log
    protected function getSqlLog($query, $isDebug = false)
    {
        $sql = $query->toSql();
        foreach ($query->getBindings() as $binding) {
            $value = is_numeric($binding) ? $binding : "'".$binding."'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        if($isDebug){
            dd($sql);
        }else{
            Log::info('SQL Query: ' . $sql);
        }
    }
}
