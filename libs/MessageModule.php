<?php
/**
 * Created by PhpStorm.
 * User: zxz
 * Date: 2018/12/2
 * Time: 下午12:48
 */

namespace zxzgit\swd\libs;


class MessageModule
{
    public $defaultController;
    /**
     * @var MessageDistributor 分发器
     */
    public $distributor;

    /**
     * @var array 模块
     */
    public $moduleList = [];

    /**
     * @var array 路由信息
     */
    public $routeInfo = [];

    /**
     * MessageModule constructor.
     * @param MessageDistributor $distributor
     * @param array $routeInfo
     */
    function __construct(MessageDistributor &$distributor,array $routeInfo)
    {
        $this->distributor = &$distributor;
        $this->routeInfo = $routeInfo;

        $this->defaultController = $this->defaultController ?: $this->distributor->connector->defaultController;

        $this->init();
    }

    /**
     * init
     */
    protected function init(){

    }


    public function run(){
        $this->parseRoute();
    }


    /**
     * 路由解析
     * @return array
     */
    protected function parseRoute()
    {
        $refObj = new \ReflectionObject($this);
        $routeInfo = $this->routeInfo;

        //路由控制
        if (isset($routeInfo[0]) && key_exists($module = $routeInfo[0], $this->moduleList)) {
            //导向模块，模块内支持嵌套模块
            $this->distributor->module = new $this->moduleList[$module]($this->distributor, array_slice($routeInfo, 1));
            return $this->distributor->module->run();
        } else {
            $action = array_pop($routeInfo);
            $controller = array_pop($routeInfo) ?: $this->defaultController;
            /** @var AbstractController $msgProcessor */
            $msgProcessorClass = implode('\\', array_filter([$refObj->getNamespaceName(), 'controllers', implode('\\', $routeInfo), ucfirst($controller) . 'Controller']));
            $msgProcessor = (new $msgProcessorClass($this->distributor, $this->distributor->frame, $this->distributor->data, ['action' => $action]));

            return $msgProcessor->run();
        }
    }
}