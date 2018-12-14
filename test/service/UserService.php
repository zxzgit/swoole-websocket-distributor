<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/19 12:21
 */

namespace zxzgit\swd\test\service;


use zxzgit\swd\test\libs\RedisKeyDict;
use zxzgit\swd\WebSocketApp;

class UserService extends BaseService {
    /**
     * 检查用户是否登录
     * @param       $fd
     * @param array $authData
     * @return bool|array
     */
    static function getLoginUser($fd, $authData = []) {
        $hasAuth = false;//是否验证成功
        if ($fd && $authData) {
            $redis = self::getRedis();

            if (!empty($authData) && isset($authData['uid']) && isset($authData['token'])) {
                $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($authData['uid']);
                $hashFdToUserKey  = RedisKeyDict::getHashFdToUser();

                $redisUserInfo = $redis->hGetAll($redisUserInfoKey);//获取用户信息

                if (
                    !empty($redisUserInfo) && $redisUserInfo['token']
                    &&
                    $authData['token'] == $redisUserInfo['token']
                    &&
                    $fd == $redisUserInfo['fd']
                    &&
                    ($fdToUid = $redis->hGet($hashFdToUserKey,$fd))//获取$fd 设置对应的用户id
                    &&
                    $fdToUid == $authData['uid']
                ) {
                    echo "用户存在验证记录,登录验证成功\n";

                    $hasAuth = true;
                } else {
                    //验证用户是否登录
                    if (
                        isset($authData['token']) && $authData['uid']
                        &&
                        ($userInfo = self::getUserByCondition(['token' => $authData['token'], 'id' => $authData['uid']], true))
                    ) {
                        $hasAuth = self::loginUser($fd, $userInfo);
                        $hasAuth && $redisUserInfo = $redis->hGetAll($redisUserInfoKey);//获取用户信息
                    }
                }
                
                if ($hasAuth) {
                    return $redisUserInfo;
                }else{
                    $redis->del($redisUserInfoKey);//删除用户信息存储 token设置为空
                    $redis->hDel($hashFdToUserKey, $fd);//fd对应uid
                }
            }
            
            
        }
        
        return null;
    }

    const LOGIN_TYPE_TOKEN = 'token';
    const LOGIN_TYPE_PASSWORD = 'password';

    /**
     * @param $fd
     * @param $userData
     * @return array|null
     */
    static function doLogin($fd, $userData)
    {
        $userInfo = null;
        if ($fd && $userData) {
            $getUserInfo = self::getUserByCondition(['name' => $userData['name'], 'password' => $userData['password'],], true);
            if ($getUserInfo) {
                $getUserInfo['uid'] = $getUserInfo['id'];
                self::loginUser($fd, $getUserInfo) && self::refreshToken($getUserInfo) && $userInfo = $getUserInfo;
            }
        }
        return $userInfo;
    }

    /**
     * 用户登陆
     * @param $fd
     * @param $userInfo
     * @return bool
     */
    static function loginUser($fd,$userInfo){
        $redis = self::getRedis();
        $redisUserInfo = [
            'uid'   => $userInfo['id'],
            'token' => $userInfo['token'],
            'name'  => $userInfo['name'],
            'fd'    => $fd,
            //其他信息
            'tokenExpireTime' => $userInfo['token_expire'],//token 需要重新刷新的时间
        ];

        $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($userInfo['id']);
        $hashFdToUserKey  = RedisKeyDict::getHashFdToUser();

        $rdSetUserInfoResult = $redis->hMset($redisUserInfoKey, $redisUserInfo);

        $rdSetFdToUidResult = $redis->hSet($hashFdToUserKey, $fd, $userInfo['id']);


        return $rdSetUserInfoResult && $rdSetFdToUidResult !== false;
    }

    /**
     * reset user token
     * @param $userInfo
     * @return bool
     */
    static function refreshToken(&$userInfo){
        $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($userInfo['uid']);

        //重新生成token
        $newToken = str_pad($userInfo['uid'], 15, 0, STR_PAD_LEFT) . '_' . time();
        $newTokenExpire = time() + 30;
        if (
            self::getRedis()->hSet($redisUserInfoKey, 'token', $newToken) !== false
            &&
            self::getRedis()->hSet($redisUserInfoKey, 'tokenExpireTime', $newTokenExpire) !== false
            &&
            self::resetTableUserToken($userInfo['uid'], $newToken, $newTokenExpire)
        ) {
            $userInfo['token'] = $newToken;
            return true;
        }

        return false;
    }

    /**
     * 重新设置用户表中的token
     * @param $uid
     * @param $newToken
     * @param $newTokenExpire
     * @return bool
     */
    static function resetTableUserToken($uid, $newToken, $newTokenExpire)
    {
        $userInfo = self::getUserByCondition(['id' => $uid], true);
        $userInfo['token'] = $newToken;
        $userInfo['token_expire'] = $newTokenExpire;
        $redisUserTableHashKey = RedisKeyDict::getUserTableHash();
        $result = self::getRedis()->hSet($redisUserTableHashKey, $uid, json_encode($userInfo));

        return $result !== false;
    }

    /**
     * 注册新用户
     * @param $newUserInfo
     */
    static function register($newUserInfo)
    {
        $redisUserTableHashKey = RedisKeyDict::getUserTableHash();
        self::getRedis()->hSet($redisUserTableHashKey, $newUserInfo['id'], json_encode($newUserInfo));

    }

    static function generalFakeUser()
    {
        self::getRedis()->del(RedisKeyDict::getUserTableHash());
        $fakeUserList = [
            ['id' => 1, 'name' => 'zxz', 'password' => '123456', 'token' => 'token_0.5623913076999942', 'token_expire' => time()+30],
            ['id' => 2, 'name' => 'ck', 'password' => '123456', 'token' => null, 'token_expire' => time()+30],
            ['id' => 3, 'name' => 'lyp', 'password' => '123456', 'token' => null, 'token_expire' => time()+30],
        ];

        foreach ($fakeUserList as $item) {
            self::register($item);
        }
    }

    static function getUserByCondition($condition, $isGetOne = true)
    {
        $resultList = [];
        $redisUserTableHashKey = RedisKeyDict::getUserTableHash();
        $userList = self::getRedis()->hGetAll($redisUserTableHashKey);
        foreach ($userList as $userInfoStr) {
            $userInfo = json_decode($userInfoStr, true);

            $isConform = true;
            foreach ($condition as $field => $value) {
                if ($userInfo[$field] != $value) {
                    $isConform = false;
                    break;
                }
            }

            if ($isConform) {
                $resultList[] = $userInfo;

                if ($isGetOne) {
                    break;
                }
            }
        }

        return $isGetOne ? array_pop($resultList) : $resultList;
    }

    /**
     * $fd 断开后清除用户redis中用户的session信息
     * @param $fd
     */
    static function closeFdClearEvent($fd)
    {
        echo "清除session";
        $redis = self::getRedis();
        $hashFdToUserKey = RedisKeyDict::getHashFdToUser();
        $uid = $redis->hGet($hashFdToUserKey, $fd);

        //删除fd对应uid信息
        $redis->hDel($hashFdToUserKey, $fd);//fd对应uid
        //删除redis用户信息
        $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($uid);
        $redis->del($redisUserInfoKey);//删除用户信息存储
    }

    /**
     * fd是否与用户session对应
     * @param $fd
     * @param bool $isReturnUserSession
     * @return array|bool
     */
    static function checkFdUserIsInSession($fd, $isReturnUserSession = false)
    {
        $redis = self::getRedis();
        $hashFdToUserKey = RedisKeyDict::getHashFdToUser();
        $fdToUid = $redis->hGet($hashFdToUserKey, $fd);//获取$fd 设置对应的用户id

        $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($fdToUid);
        $redisUserInfo = $redis->hGetAll($redisUserInfoKey);//获取用户信息


        if ($fd == $redisUserInfo['fd']) {
            if ($isReturnUserSession) {
                return $redisUserInfo;
            }
            return true;
        }

        return false;
    }
}