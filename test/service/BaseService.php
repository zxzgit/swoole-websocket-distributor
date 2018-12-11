<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/19 12:21
 */

namespace zxzgit\swd\test\service;


class BaseService {
    static private $redis;
    static function getRedis(){
        if(!self::$redis){
            self::$redis = new \Redis();
            self::$redis->connect('127.0.0.1', 6379);
        }

        return self::$redis;
    }
}