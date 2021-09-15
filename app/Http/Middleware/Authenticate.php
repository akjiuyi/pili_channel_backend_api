<?php

namespace App\Http\Middleware;

use App\Models\Channel;
use Closure;
use Illuminate\Support\Facades\Cache;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->input('token');
        if (!$token) {
            $token = $request->header('token');
            if (!$token) {
                $token = $request->cookie('token');
            }
        }
        if (!$token) {
            return response()->json(['code' => 401, 'msg' =>'token not found ']);
        }

        $channelInfo = Channel::getInfoByToken($token);
        if (!$channelInfo) {
            return response()->json(['code' => 401, 'msg' =>'users not found']);
        }

        Cache::put('token:' . $token, $channelInfo, Channel::TOKEN_EXPIRED);

        $request->attributes->add(['channelInfo' => $channelInfo]);

        return $next($request);
    }
}
