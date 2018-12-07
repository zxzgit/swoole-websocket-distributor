<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/19 16:45
 */

namespace zxzgit\swd\test\service;


use zxzgit\swd\test\libs\RedisKeyDict;
use zxzgit\swd\WebSocketApp;

class RoomService extends BaseService {
    static function fdRelativeToRoom($fd, $roomId) {
        $redisKey = RedisKeyDict::getHashRoomFdList($roomId);
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->zAdd($redisKey, time(), $fd);
    }
}