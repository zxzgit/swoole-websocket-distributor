<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/16 16:32
 */

namespace zxzgit\swd\libs;


class MessageDistributor{
    /**
     * @var ConnectHandler $connector
     */
    public $connector;
    public $frame;
    public $route;
    public $data;

    /**
     * @var array 子模块列表
     */
    public $moduleList = [];

    /**
     * @var MessageModule
     */
    public $module;

    function __construct(&$connector, &$frame, $data)
    {
        $this->connector = &$connector;
        $this->frame = $frame;
        $this->route = $this->parseRoute($data);
        $this->data = $data;
    }

    public function run()
    {
        return $this->dealRoute();
    }

    /**
     * 解析路由
     */
    public function parseRoute($data)
    {
        //如果定义了自定义解析路由函数
        $routeDataFormat = $this->connector->parseRouteDataFormat;
        if (isset($this->connector->parseRouteMap[$routeDataFormat]) && is_callable($this->connector->parseRouteMap[$routeDataFormat])) {
            return call_user_func($this->connector->parseRouteMap[$routeDataFormat], $data);
        } else {
            throw new \Exception("route data format method '{$routeDataFormat}' can't find in ConnectCollection::parseRouteDataFormat property");
        }
    }

    /**
     * 简单路由到控制器
     * @return array
     */
    public function dealRoute()
    {
        $refObj = new \ReflectionObject($this);
        $routeInfo = explode('/', $this->route);

        //路由控制
        if (isset($routeInfo[0]) && key_exists($module = $routeInfo[0], $moduleList = $this->connector->moduleList + $this->moduleList)) {
            //导向模块
            $this->module = new $moduleList[$module]($this, array_slice($routeInfo, 1));
            return $this->module->run();
        } else {
            $action = array_pop($routeInfo);
            $controller = array_pop($routeInfo) ?: $this->connector->defaultController;
            /** @var AbstractController $msgProcessor */
            $msgProcessorClass = implode('\\', array_filter([$refObj->getNamespaceName(), 'controllers', implode('\\', $routeInfo), ucfirst($controller) . 'Controller']));
            $msgProcessor = (new $msgProcessorClass($this, $this->frame, $this->data, ['action' => $action]));

            return $msgProcessor->run();
        }
    }
}