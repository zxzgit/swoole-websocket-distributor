<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\controllers;

use zxzgit\swd\test\service\UserService;

class UserController extends BaseController {
    public $authActionList = ['checkLogin'];
    public function actionLogin() {
        $userInfo = UserService::doLogin($this->frame->fd, $this->data);
        if ($userInfo) {
            unset($userInfo['password']);
            unset($userInfo['token_expire']);
            return $this->pushMsg($userInfo);
        }
        return $this->pushMsg(['msg' => '登陆失败'], 401);
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