<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\controllers;

use zxzgit\swd\test\libs\RedisKeyDict;
use zxzgit\swd\test\service\RoomService;
use zxzgit\swd\test\service\UserService;

class RoomController extends BaseController {
    public $authActionList = ['interRoom'];
    public function actionInterRoom() {
        if (isset($this->data['roomId']) && $this->data['roomId']) {
            $roomId = $this->data['roomId'];
            if($roomId){
                //发送消息给房间里面的人
                $redisKey = RedisKeyDict::getHashRoomFdList($roomId);
                $start    = 0;
                $batchNum = 100;
                $redis    = UserService::getRedis();

                while ($batchFdList = $redis->zRevRange($redisKey, $start, $start + ($batchNum - 1))) {
                    //给同房间的用户发通知
                    foreach ($batchFdList as $fd) {
                        if ($this->isExistFd($fd)) {
                            $fd != $this->frame->fd && $this->pushMsg([
                                'content' => $this->getUser()['name'] . '进入房间',
                                'fromUser' => '',
                            ], 200, $fd, '', ['event' => 'notifyUserInterRoom']);
                        } else {
                            RoomService::removeDisconnectFdFromRoom($fd, $roomId);
                        }
                    }
                    $start = $start + $batchNum;
                }

                RoomService::fdRelativeToRoom($this->frame->fd, $roomId);
            }
            
            return $this->pushMsg(['content' => 'you inter room event already to notify roommate']);
        } else {
            return $this->pushMsg(['msg' => 'not get RoomId'],400);
        }
    }
}