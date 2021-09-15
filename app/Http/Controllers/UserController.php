<?php
namespace App\Http\Controllers;


use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function info(Request $request) {
        $channelInfo = $request->get('channelInfo');
        return $this->successJson([
            'username' => $channelInfo->nickname,
            'avatar' => '',
            'phone' => $channelInfo->phone,
            'lastLoginDate' => displayCreatedTime($channelInfo->last_login_time)
        ]);
    }

    public function resetPwd(Request $request) {
        $channelInfo = $request->get('channelInfo');
        $password = trim($request->input('password'));
        $newPassword = trim($request->input('newPassword'));
        Channel::resetPwd($channelInfo->id, $password, $newPassword);
        return $this->successJson(['message' => '修改成功', 'resetToken' => true]);
    }
}