<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:23
 */

namespace zxzgit\swd\test\controllers;


class IndexController extends BaseController {
    
    public function actionIndex() {
        
        return $this->pushMsg(['hello', 'world']);
    }
}