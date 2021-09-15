<?php
/**
 * Created by PhpStorm.
 * User: yy
 * Date: 2021/6/4
 * Time: 11:24
 */

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request) {
        $s = \Illuminate\Support\Facades\Cache::put('testexpire', 1111, 4);

        var_dump($s);echo PHP_EOL;
    }
}