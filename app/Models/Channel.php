<?php
namespace App\Models;
use App\Exceptions\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Channel extends Model
{
    protected $table = 'mzfk_channel';
    protected $dateFormat = 'U';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    //const TOKEN_EXPIRED = 3600;
    const TOKEN_EXPIRED = 3600*24;

    public static function getInfoById($channelId) {
        return self::where('id', $channelId)->first();
    }

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
        $channelInfo = self::query()->where('channel_name', trim($username))->selectRaw("id,pwd,channel_name as nickname,last_login_time,token,token_expired")->first();

        if (!$channelInfo) {
            throw new ServiceException("账户不存在");
        }

        //校验密码
        if (self::encryptPwd($password) != $channelInfo['pwd']) {
            throw new ServiceException("密码错误");
        }

        //Cache::forget('token:' . $channelInfo->token);
        //更新token，更新token过期时间
        $channelInfo->token           = self::generateToken($channelInfo->id);
        $channelInfo->token_expired   = time() + self::TOKEN_EXPIRED;
        $channelInfo->last_login_time = time();
        $channelInfo->last_login_ip   = ip2long(getRealIp());

        if (!$channelInfo->save()) {
            throw new ServiceException("登陆失败");
        }
        return $channelInfo;
    }

    public static function resetPwd($channelId, $password, $newPassword) {
        $channelInfo = self::query()->where('id', $channelId)->selectRaw("id,pwd,channel_name as nickname,last_login_time,token,token_expired")->first();

        if (!$channelInfo) {
            throw new ServiceException("账户不存在");
        }

        //校验密码
        if (self::encryptPwd($password) != $channelInfo['pwd']) {
            throw new ServiceException("原始密码错误");
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $newPassword)) {
            throw new ServiceException("请输入3-20位字符,字符必须是英文、字母、下划线");
        }

        $channelInfo->pwd = self::encryptPwd($newPassword);
        if (!$channelInfo->save()) {
            throw new ServiceException("修改密码失败");
        }

        Cache::forget('token:' . $channelInfo->token);
    }

    public static function getInfoByChannelIdForLock($channelId) :? self{
        return parent::where('id', $channelId)->lockForUpdate()->first();
    }

}
