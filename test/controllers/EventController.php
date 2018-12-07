<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\controllers;


class EventController extends BaseController {
    
    public function actionInterRoom() {
        return $this->pushMsg(['event', 'msg']);
    }
}