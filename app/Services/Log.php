<?php
/**
 *  日志记录
 */

namespace App\Services;


use Monolog\Logger;

class Log
{
    private $_log = [];

    private function init($name): Logger
    {
        if (empty($this->_log[$name])) {
            $this->_log[$name] = (new Logger($name))->pushHandler(new \Monolog\Handler\RotatingFileHandler(app()->storagePath() . '/logs/' . $name . '.log'));
        }
        return $this->_log[$name];
    }

    public function debug($msg, $fileName, $context = null)
    {
        $context = $context ? [$context] : [];
        return $this->init($fileName)->debug($msg, $context);
    }

    public function error($msg, $fileName, $context = null)
    {
        $context = $context ? [$context] : [];
        return $this->init($fileName)->error($msg, $context);
    }

    public function sqlError($sql, $fileName, $context = null)
    {
        $context = $context ? [$context] : [];
        return $this->init($fileName)->warn($sql, $context);
    }
}