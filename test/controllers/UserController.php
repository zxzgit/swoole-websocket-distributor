<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\controllers;

use zxzgit\swd\test\service\UserService;

class UserController extends BaseController {
    
    public function actionLogin() {
        $result = UserService::doLogin($this->frame->fd, $this->data);
        return $this->pushMsg(['hello', 'world']);
    }
    
    /**
     * 检测是否登录
     * @return mixed
     */
    public function actionCheckLogin() {
        return $this->pushMsg(['isLogin' => $this->checkUserLogin()]);
    }

    /**
     * 刷新token操作
     */
    public function actionRefreshToken(){
        if (!$this->checkUserLogin()) {
            return $this->pushMsg(['type' => 'noLogin'], 403);
        }

        //刷新token
        if (UserService::refreshToken($this->user)) {
            return $this->pushMsg($this->user, 200);
        } else {
            return $this->pushMsg([], 500);
        }
    }
}