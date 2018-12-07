<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\controllers;

use zxzgit\swd\test\libs\RedisKeyDict;
use zxzgit\swd\test\service\RoomService;

class RoomController extends BaseController {
    
    public function actionInterRoom() {
        if ($this->checkUserLogin() && isset($this->data['roomId']) && $this->data['roomId']) {
            $roomId = $this->data['roomId'];
            $roomId && RoomService::fdRelativeToRoom($this->frame->fd, $roomId);
            
            //发送消息给房间里面的人
            $redisKey = RedisKeyDict::getHashRoomFdList($roomId);
            $start    = 0;
            $batchNum = 100;

            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);

            while ($batchFdList = $redis->zRevRange($redisKey, $start, $start + ($batchNum - 1))) {
                //给同房间的用户发通知
                foreach ($batchFdList as $fd) {
                    $this->frame->fd !=$fd && $this->pushMsg([
                        'msg' => $this->frame->fd . '进入房间',
                        'fromUser' => '',
                    ], 200, $fd);
                }
                $start = $start + $batchNum;
            }
            
            
            return $this->pushMsg(['inter room', 'user is login']);
        } else {
            return $this->pushMsg(['inter room,', 'user is no login']);
        }
    }
}