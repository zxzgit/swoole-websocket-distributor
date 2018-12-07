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
    const RETURN_CODE = [
        200 => 'Success',
        403 => 'Forbidden',
    ];
    
    /**
     * @var MessageDistributor $distributor
     */
    public    $distributor;
    public    $frame;
    public    $distributorData;
    public    $action;
    public    $data        = [];
    protected $isLoginUser = null;
    protected $user        = false;
    
    
    function __construct(&$distributor, $frame, $distributorData, $config = []) {
        $this->distributor     = &$distributor;
        $this->frame           = $frame;
        $this->distributorData = $distributorData;
        $this->init($config);
    }
    
    /**
     * @return array
     */
    public function run() {
        $eventType = 'action' . ucfirst($this->action);
        
        return $this->$eventType();
    }
    
    /**
     * 检查用户是否处于登录
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
            $this->user = UserService::getLoginUser($this->frame->fd, $this->data);
        }
        
        return $this->user;
    }
}