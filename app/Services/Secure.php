<?php
namespace App\Services;

class Secure {

    public $realIp;

    public function __construct()
    {
        $this->realIp = getRealIp();
    }

    public function judgeIP() {
        $key = 'Illegal:Forbidden:'.$this->realIp;
//        $error = Redis::getInstance()->get($key);
        $error = 0;
        if ($error > 2) {
            logDebug('ip=' . $this->realIp, 'ipJudge');
//            header("HTTP/1.1 204 Not Content");
//            die;
        }
        return true;
    }

    public function forbiddenIP()
    {
        return null;
//        $key = 'Illegal:Forbidden:'.$this->realIp;
//        if(Redis::getInstance()->setnx($key,1)){
//            Redis::getInstance()->expire($key,600);
//        }else{
//            Redis::getInstance()->incrby($key,1);
//        }
    }

}