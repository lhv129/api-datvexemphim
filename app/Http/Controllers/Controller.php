<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function responseCommon($httpCode,$message,$data){
        $pattern = '/^2\d{2}$/';
        $status = false;
        if(preg_match($pattern,$httpCode) === 1){
            $status = true;
        }
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ],$httpCode);
    }

    public function responseError($httpCode,$message,$errors){
        $pattern = '/^2\d{2}$/';
        $status = false;
        if(preg_match($pattern,$httpCode) === 1){
            $status = true;
        }
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => $errors
        ],$httpCode);
    }
}
