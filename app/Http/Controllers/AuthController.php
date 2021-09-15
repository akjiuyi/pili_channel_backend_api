<?php
namespace App\Http\Controllers;
use App\Models\Channel;
use \Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['login']]);
    }

     public function login(Request $request) {
         $username = trim($request->input('username'));
         $password = trim($request->input('password'));
         $channelInfo = Channel::login($username, $password);
         Cache::put('token:' . $channelInfo->token, $channelInfo, Channel::TOKEN_EXPIRED);
         return $this->successJson(['token' =>$channelInfo->token]);
     }

     public function logout(Request $request) {
         $channelInfo = $request->get('channelInfo');
         Cache::forget('token:' . $channelInfo->token);
         return $this->successJson([]);
     }
}
