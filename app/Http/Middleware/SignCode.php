<?php
/**
 * User: Admin
 * Date: 2020/5/19
 */

namespace App\Http\Middleware;
use App\Models\Game\ChannelList;
use App\Services\Redis;
use App\Services\Secure;
use Illuminate\Http\Request;
use Closure;

class SignCode
{

    public function handle(Request $request, Closure $next)
    {
        $agent = $request->input('agent');
        $signCode = $request->input('SignCode');
        if(!$signCode){
            return response()->json(['code' => VERSION_ERROR, 'msg' => '参数有误']);
        }
        $key = 'Channel:List:'.$signCode;
//        $info = Redis::getInstance()->get($key);
        $channel = null;
        if(0){
//            $channel = json_decode($info,true);
        } else {
            $channel = (new ChannelList())->getChannel($agent,$signCode);
            $channel = $channel ? $channel->toArray() : null;
        }
        if($channel){
//            Redis::getInstance()->set($key,json_encode($channel));
//            Redis::getInstance()->expire($key, 10);
            $request->attributes->add(['channelData' => $channel]);
            return $next($request);
        } else {
//            (new Secure())->forbiddenIP();

            app()->configure('defense');
            $msgArr = config('defense.error_code');
            $data['code'] = CACHE_INVALID;
            $data['msg'] = $msgArr[CACHE_INVALID] ?? '';
            return response()->json($data);
        }
    }
}