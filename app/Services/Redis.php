<?php
/**
 * redis 操作
 */

namespace App\Services;

//ini_set('default_socket_timeout', -1);

class Redis
{
    /**
     * @param string $connection
     * @return \Illuminate\Redis\Connections\PhpRedisConnection db0
     */
    public static function getInstance($connection = 'default', $ping = true)
    {
        $redisManager = app()->make('redis');
        $redis = $redisManager->connection($connection);
        try {
            if ($ping == true) {
                if ($redis->ping() == '+PONG') {
                    return $redis;
                } else {
                    $redis->close();  //phpRedis 4.2.0
                }
            } else {
                return $redis;
            }
        } catch (\Exception $exception) {
            logError($exception);
            try {
                $redis->close();  //phpRedis 4.2.0
            } catch (\Exception $exception) {
                logError($exception);
            }
        }

        return $redisManager->connection($connection);
    }


    /** 并发锁 (true 表示获得锁)
     * @param $key
     * @param int $ttl //多少秒内只允许执行一次
     * @return bool
     * @throws \Exception
     */
    public static function concurrentLock($key, $ttl = 3)
    {
        try {
            $random = rand(1, 999);
            $ok = self::getInstance()->set('__RedisLock__' . $key, $random, 'EX', $ttl, 'NX');
            if ($ok) {
                return true;
                /* if ($_redis->get($key) == $random) {
                     $_redis->del($key);
                 }*/
            } else {
                return false;
            }
        } catch (\Exception $e) {
            logError($e);
            return false;
        }
    }

    /** 多少秒内，限制访问数
     * @param $key
     * @param int $second
     * @param int $num
     * @return bool
     */
    public static function limitRequest($key, $second = 1, $num = 10)
    {
        $key = '_limitRequest_' . $key;
        $redis = self::getInstance();
        $len = $redis->llen($key);
        if ($len > $num) {
            return false;
        }

        if (!$redis->exists($key)) {
            $multi = $redis->multi();
            $multi->rpush($key, 1);
            $multi->expire($key, $second);
            $multi->exec();
            return true;
        } else {
            return (bool)$redis->rpushx($key, 1);
        }
    }

    /**
     * notes:
     * author: CL
     * @param $key
     * @param string $value
     * @param int $lifeTime
     * @return int
     */
    public static function setWaitLock($key, $value = '', $lifeTime = 86400) {
        $key = '_waitQueueLock_' . $key;
        $redis = self::getInstance();
        if ($lifeTime !== null) {
            $redis->expire($key, $lifeTime);
        }
        return $redis->lpush($key, [$value]);
    }

    /**
     * notes: 等待锁
     * author: CL
     * @param $key
     * @param int $timeOut // 等待超时时间
     */
    public static function waitLock($key, $timeOut = 3600) {
        $key = '_waitQueueLock_' . $key;
        $redis = self::getInstance();
        return $redis->brpoplpush($key, $key, $timeOut);
    }
}