<?php
namespace App\Models;
use App\Exceptions\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdmMember extends Model
{
    protected $table = 'mzfk_adm_member';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    const TOKEN_EXPIRED = 3600;

    public static function encryptPwd($password) {
        return $password ? md5($password . '_channelAdmin_') : '';
    }

    public static function generateToken($uid = 0){
        return md5($uid . '_' . randString() . date("YmdHis"));
    }

    public static function getInfoByToken(string $token) {
        return Cache::get('token:' . $token);
    }

    public static function login($username, $password) {
        $userInfo = self::query()->where('nickname', trim($username))->selectRaw("id,email,pwd,app_group,state,accid,nickname,last_login_time,token,token_expired,role_id")->first();

        if (!$userInfo) {
            throw new ServiceException("账户不存在");
        }

        //校验密码
        if (self::encryptPwd($password) != $userInfo['pwd']) {
            throw new ServiceException("密码错误");
        }

        if ($userInfo->state != 1) {
            throw new ServiceException("账号不可用");
        }
        Cache::forget('token:' . $userInfo->token);
        //更新token，更新token过期时间
        $userInfo->token           = self::generateToken($userInfo->id);
        $userInfo->token_expired   = time() + self::TOKEN_EXPIRED;
        $userInfo->last_login_time = time();
        $userInfo->last_login_ip   = ip2long(getRealIp());

        if (!$userInfo->save()) {
            throw new ServiceException("登陆失败");
        }
        return $userInfo;
    }

}