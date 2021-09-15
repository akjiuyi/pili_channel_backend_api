<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerJumps extends Model
{
    protected $connection = 'admin';
    protected $table = 'server_jumps';

    /**
     * 获取有效跳板机服务器地址
     */
    public static function getLiveServer($signCode, $bid = 0) {
        $info = parent::query()
            ->where('state', 1)
            ->where('channel_sign', $signCode)
            ->where('id', '>', $bid)
            ->first();
        if (!$info) {
            return '';
        }
        $bid ++;
//        $isLive = self::checkServerLive( $info->server_ip, $info->check_port);
//        if (!$isLive) { // 服务器不存活
//            $info->state = 2;
//            $info->refuse_time = time();
//            $info->save();
//            return self::getLiveServer($signCode, $bid);
//        }
        return $info->server_ip;
    }

    public static function getLiveServerInfo($signCode, $bid = 0) {
        $info = parent::query()
            ->where('state', 1)
            ->where('channel_sign', $signCode)
            ->where('id', '>', $bid)
            ->first();
        if (!$info) {
            return null;
        }
        $bid ++;
//        $isLive = self::checkServerLive( $info->server_ip, $info->check_port);
//        if (!$isLive) { // 服务器不存活
//            $info->state = 2;
//            $info->refuse_time = time();
//            $info->save();
//            return self::getLiveServer($signCode, $bid);
//        }
        return $info;
    }

    /**
     * 检查服务器存活, ping ip返回
     * 成功       Array
    //        (
    //            [0] => PING 192.168.1.115 (192.168.1.115) 56(84) bytes of data.
    //            [1] => 64 bytes from 192.168.1.115: icmp_seq=1 ttl=64 time=0.721 ms
    //            [2] =>
    //            [3] => --- 192.168.1.115 ping statistics ---
    //            [4] => 1 packets transmitted, 1 received, 0% packet loss, time 0ms
    //            [5] => rtt min/avg/max/mdev = 0.721/0.721/0.721/0.000 ms
    //)

    // 失败       Array
    //        (
    //            [0] => PING 192.168.0.115 (192.168.0.115) 56(84) bytes of data.
    //        [1] =>
    //    [2] => --- 192.168.0.115 ping statistics ---
    //    [3] => 2 packets transmitted, 0 received, 100% packet loss, time 999ms
    //        [4] =>
    //)
     */
    public static function checkServerLive($serverIp, $checkPort = 80) {
//        exec("ping -c 4 -w 1 $serverIp", $output, $return_var); // -c  ping的次数， -w  超时时间
//        $len = count($output);
//        if (!$output[1] && !$output[$len - 1]) {
////            echo "ping 不通";
//            return false;
//        }
        if (filter_var($serverIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {        //IPv6
            $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        } elseif (filter_var($serverIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {    //IPv4
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } else {
//            echo 'socket 创建失败';
            return false;
        }
        @$ok = socket_connect($socket, $serverIp, $checkPort);
        socket_close($socket);
        if ($ok) {
//            echo "连接OK\n";
            return true;
        } else {
//            echo "socket_connect() failed. Reason: ($ok) " . socket_strerror($ok) . "\n";
            return false;
        }
    }

    public static function checkFailServerLive() {
        set_time_limit(600);
        $lists = parent::query()->where('state', 2)->get();
        foreach($lists as $info) {
            $live = self::checkServerLive($info->server_ip, $info->check_port);
            if ($live) {
                $info->state = 1;
                $info->refuse_time = 1;
                $info->retry_times = 0;
            } else {
                $info->retry_times =  $info->retry_times + 1;
            }
            $info->save();
        }
    }
}