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
     * @param array $userData
     * @return bool|array
     */
    static function getLoginUser($fd, $userData = []) {
        $hasAuth = false;//是否验证成功
        if ($fd && $userData) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);


            $receiveInfo = $userData;
            if (!empty($receiveInfo) && isset($receiveInfo['uid']) && isset($receiveInfo['token'])) {
                $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($receiveInfo['uid']);
                $hashFdToUserKey  = RedisKeyDict::getHashFdToUser();
                
                $redisUserInfo = $redis->hGetAll($redisUserInfoKey);//获取用户信息

                if (
                    !empty($redisUserInfo) && $redisUserInfo['token']
                    &&
                    $receiveInfo['token'] == $redisUserInfo['token']
                    &&
                    $fd == $redisUserInfo['fd']
                    &&
                    ($fdToUid = $redis->zScore($hashFdToUserKey,$fd))//获取$fd 设置对应的用户id
                    &&
                    $fdToUid == $receiveInfo['uid']
                ) {
                    echo "用户存在验证记录,登录验证成功\n";
                    
                    $hasAuth = true;
                } else {
                    //todo 验证用户是否登录
                    if (isset($receiveInfo['token'])) {
                        $redisUserInfo = [
                            'uid'   => $receiveInfo['uid'],
                            'token' => $receiveInfo['token'],
                            'name'  => $receiveInfo['name'],
                            'fd'    => $fd,
                            //todo 其他信息
                        ];
                        $rdSetUserInfoResult = $redis->hMset($redisUserInfoKey, $redisUserInfo);
                        
                        $rdSetFdToUidResult = $redis->zAdd($hashFdToUserKey, $receiveInfo['uid'], $fd);
                        
                        
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
    
    static function doLogin($fd, $userData){
        if($fd && $userData){
            //todo 验证参数是否正确
            if(isset($userData['name']) && isset($userData['password'])){
                //todo 验证密码是否正确
                
                //todo 验证登录密码
            }
        }
        
        return false;
    }
}