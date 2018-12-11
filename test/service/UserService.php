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
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);

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
                    ($fdToUid = $redis->zScore($hashFdToUserKey,$fd))//获取$fd 设置对应的用户id
                    &&
                    $fdToUid == $authData['uid']
                ) {
                    echo "用户存在验证记录,登录验证成功\n";

                    $hasAuth = true;
                } else {
                    //todo 验证用户是否登录
                    if (
                        isset($authData['token'])
                        &&
                        ($userInfo = self::getUserByCondition(['token' => $authData['token'], 'id' => $authData['uid']], true))
                    ) {
                        $redisUserInfo = [
                            'uid'   => $userInfo['id'],
                            'token' => $userInfo['token'],
                            'name'  => $userInfo['name'],
                            'fd'    => $fd,
                            //todo 其他信息
                            'tokenExpireTime' => $userInfo['token_expire'],//token 需要重新刷新的时间
                        ];
                        $rdSetUserInfoResult = $redis->hMset($redisUserInfoKey, $redisUserInfo);

                        $rdSetFdToUidResult = $redis->zAdd($hashFdToUserKey, $userInfo['id'], $fd);
                        
                        
                        $hasAuth = $rdSetUserInfoResult && $rdSetFdToUidResult !== false;
                    }
                }
                
                if ($hasAuth) {
                    return $redisUserInfo;
                }else{
                    $redis->del($redisUserInfoKey);//删除用户信息存储 token设置为空
                    $redis->zRem($hashFdToUserKey, $fd);//fd对应uid
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
     * @param $loginType
     * @return array|null
     */
    static function doLogin($fd, $userData, $loginType)
    {
        $userInfo = null;
        if ($fd && $userData) {
            if ($loginType == self::LOGIN_TYPE_TOKEN) {
                $userInfo = self::getUserByCondition(['token' => $userData['token']], true);
            } elseif ($loginType == self::LOGIN_TYPE_PASSWORD) {
                $userInfo = self::getUserByCondition(['name' => $userData['name'], 'password' => $userData['password'],], true);
            }
        }

        return $userInfo;
    }

    /**
     * 用户登陆成功
     * @param $userInfo
     */
    static function loginUser($userInfo){

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
}