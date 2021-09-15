<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\Cache;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successJson(array $data = null, $code = 200)
    {
        $data['code'] = $code;
        $data['msg'] = '';
        return response()->json($data);
    }

    public function errorJson($msg = '操作失败', $code = 400)
    {
        $data['code'] = $code;
        $data['msg'] = $msg;
        return response()->json($data);
    }

//    public function getUser() {
//        return \Illuminate\Support\Facades\Cache::get();
//    }
}
