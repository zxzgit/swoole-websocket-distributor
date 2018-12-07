<?php

/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/7/12 19:52
 */


include '../../vendor/autoload.php';

use \zxzgit\swd\test\libs\RedisKeyDict;

\zxzgit\swd\WebSocketApp::run([
    'moduleList' => [
        'test' => \zxzgit\swd\test\modules\test\MessageModule::class,
    ],
    'messageDistributor' => \zxzgit\swd\test\MessageDistributor::class,
    'event' => [
        'initConnector' => function () {
            return;
            /** @var \Redis $redis */
            $initBatchClearNum = 1000;
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $hashFdToUserKey = RedisKeyDict::getHashFdToUser();

            echo "初始化删除上次退出遗留redis信息开始：" . PHP_EOL;
            //按批次删除退出时redis保存的链接信息与用户信息
            while ($remainFdToUid = $redis->zRevRange($hashFdToUserKey, 0, $initBatchClearNum - 1, true)) {
                $batchDelUidKey = [];
                foreach ($remainFdToUid as $fd => $uid) {
                    $batchDelUidKey[] = RedisKeyDict::getHashUserInfoKey($uid);
                }

                echo "本批次要删除的uid：" . PHP_EOL;
                //print_r($batchDelUidKey);

                $deleteUserInfoNum = $redis->del($batchDelUidKey);//执行批量的删除用户信息

                if ($deleteUserInfoNum == count($remainFdToUid)) {
                    $redis->zRemRangeByRank($hashFdToUserKey, 0, $initBatchClearNum - 1);
                }
            }

            $redis->del($hashFdToUserKey);//fd与用户对应关系

            echo "初始化删除上次退出遗留redis信息完成" . PHP_EOL;
        },
        'workerStart' => function () {
            echo "workerStart event";
        },
        'open' => function (&$server,&$req) {
            echo "connection open: {$req->fd}\n";
            echo "open event";
        },
        'beforeMessage' => function () {
            echo "beforeMessage event";
        },
        'afterMessage' => function () {
            echo "afterMessage event";
        },
        'close' => function (&$server, $fd) {
            return;
            echo "close event";
            //移除该链接用户信息
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $hashFdToUserKey = RedisKeyDict::getHashFdToUser();
            $uid = $redis->zScore($hashFdToUserKey, $fd);

            //删除fd对应uid信息
            $redis->zRem($hashFdToUserKey, $fd);//fd对应uid
            //删除redis用户信息
            $redisUserInfoKey = RedisKeyDict::getHashUserInfoKey($uid);
            $redis->del($redisUserInfoKey);//删除用户信息存储
        },
    ]
]);
