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
        $redis = self::getRedis();
        $redis->zAdd($redisKey, time(), $fd);
    }

    static function removeDisconnectFdFromRoom($fd, $roomId){
        $redisKey = RedisKeyDict::getHashRoomFdList($roomId);
        $redis = self::getRedis();
        $redis->zDelete($redisKey,$fd);
    }
}