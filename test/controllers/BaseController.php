<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:25
 */

namespace zxzgit\swd\test\controllers;

use zxzgit\swd\test\MessageDistributor;
use zxzgit\swd\test\service\UserService;

class BaseController extends \zxzgit\swd\libs\AbstractController {
    public $authActionList = [];

    protected $isLoginUser = null;
    protected $user        = false;

    protected function beforeAction()
    {
        if (in_array($this->action, $this->authActionList)) {
            if (!$this->checkUserLogin()) {//未登陆
                $returnData = [
                    'preRequestInfo' => $this->parsedMsgData,
                    'type' => 'noLogin',
                ];
                $this->pushMsg($returnData, 403);
                return false;
            }

            if ($this->isTokenExpire()) {//token过期
                $returnData = [
                    'preRequestInfo' => $this->parsedMsgData,
                    'type' => 'refreshToken',
                ];
                $this->pushMsg($returnData, 403);
                return false;
            }

        }
        return parent::beforeAction();
    }

    /**
     * 检查用户是否处于登录
     * @param bool $isCheckTokenExpire 是否检车token过期
     * @return bool
     */
    public function checkUserLogin() {
        if ($this->isLoginUser === null) {
            $this->isLoginUser = !is_null($this->getUser());
        }

        return $this->isLoginUser;
    }
    
    /**
     * 获取用户
     * @return null|array 如果用户不合法，则返回null,如果是登录用户，返回登录用户信息
     */
    public function getUser() {
        if ($this->user === false) {
            $this->user = UserService::getLoginUser($this->frame->fd, $this->parsedMsgData['auth']);
        }
        
        return $this->user;
    }

    /**
     * token是否过期，需要重新刷新
     * @return bool
     */
    public function isTokenExpire(){
        if(!isset($this->getUser()['tokenExpireTime']) || $this->getUser()['tokenExpireTime'] < time()){
            return true;
        }

        return false;
    }
}