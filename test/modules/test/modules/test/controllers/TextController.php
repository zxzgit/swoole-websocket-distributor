<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\modules\test\modules\test\controllers;

use zxzgit\swd\test\controllers\BaseController;
use zxzgit\swd\test\libs\RedisKeyDict;
use zxzgit\swd\test\service\UserService;

class TextController extends BaseController {
    public $authActionList = ['forRoom'];
    public function actionForRoom() {
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
                        if($this->isExistFd($fd) && $fd != $this->frame->fd && UserService::checkFdUserIsInSession($fd)){
                            $this->pushMsg([
                                'content' => $this->data['content'],
                                'fromUser' => $this->getUser()['name'],
                            ], 200, $fd, '', ['event' => 'notifyUserInterRoom']);
                        }
                    }
                    $start = $start + $batchNum;
                }
            }

            return $this->pushMsg([], 200, $this->frame->fd, 'success');
        } else {
            return $this->pushMsg([], 400, $this->frame->fd, 'miss roomId');
        }
    }
}