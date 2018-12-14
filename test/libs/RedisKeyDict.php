<?php

namespace zxzgit\swd\test\libs;

class RedisKeyDict {
    const KEY_PREFIX = 'rsc:';
    
    /**
     * 给定key添加上前缀
     * @param $key
     * @return string
     */
    static function getFormat($key){
        return self::KEY_PREFIX . $key;
    }
    
    /**
     * 用户信息存储
     * @param $roomId
     * @return string
     */
    static function getHashUserInfoKey($uid) {
        return self::getFormat("user:session:$uid");
    }
    
    /**
     * 在线fd对应用户id
     * @return string
     */
    static function getHashFdToUser() {
        return self::getFormat("fdToUid:hash");
    }
    
    /**
     * 获取当前房间链接的fd
     * @return string
     */
    static function getHashRoomFdList($roomId) {
        return self::getFormat("room:fdList:$roomId");
    }

    /**
     * 用户信息存储表，懒的使用数据库
     * @return string
     */
    static function getUserTableHash(){
        return self::getFormat("table:user");
    }
}