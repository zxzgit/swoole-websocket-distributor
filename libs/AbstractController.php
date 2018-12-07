<?php
/**
 * Created by zxzTool.
 * User: zxz
 * Datetime: 2018/11/15 18:25
 */

namespace zxzgit\swd\libs;


abstract class AbstractController {
    const RETURN_CODE = [
        200 => 'Success',
        403 => 'Forbidden',
    ];

    /**
     * @var MessageDistributor $distributor
     */
    public $distributor;

    /**
     * @var $frame
     */
    public $frame;

    /**
     * @var 客户端发送的原始数据
     */
    public $distributorData;

    /**
     * @var route action
     */
    public $action;

    /**
     * @var main data
     */
    public $data;

    /**
     * @var parse data from distributor data
     */
    public $parsedMsgData;

    /**
     * @var string main data property name in $parsedMsgData while use default parse
     */
    public $dataPropertyInParsedMsgData = 'data';
    
    function __construct(&$distributor, $frame, $distributorData, $config = []) {
        $this->distributor     = &$distributor;
        $this->frame           = $frame;
        $this->distributorData = $distributorData;
        $this->init($config);
    }

    /**
     * @param $config
     */
    protected function init($config) {
        foreach ($config as $configKey => $configVal) {
            if (property_exists(get_class($this), $configKey)) {
                $this->$configKey = $configVal;
            }
        }

        $this->parsedMsgData = $this->parseMsgData();
        $this->data = $this->parseData();
    }

    /**
     * 从原始传输数据中解析出数据,可重写自定义
     * @return mixed
     */
    protected function parseMsgData()
    {
        return json_decode($this->distributorData, true);
    }

    /**
     * 从接受数据中解析主要数据,可重写自定义
     * @return array
     */
    protected function parseData()
    {
        return isset($this->parsedMsgData[$this->dataPropertyInParsedMsgData]) ? $this->parsedMsgData[$this->dataPropertyInParsedMsgData] : [];
    }
    
    /**
     * @return array
     */
    public function run() {
        $eventType = 'action' . ucfirst($this->action);
        
        return $this->$eventType();
    }

    /**
     * 服务端向客户端发送信息 https://wiki.swoole.com/wiki/page/399.html
     * @param array $data
     * @param int $code
     * @param null $fd
     * @param int $opCode
     * @param bool $finish
     * @return mixed
     * @throws \Exception
     */
    public function pushMsg($data = [], $code = 200, $fd = null, $opCode = 1, $finish = true) {
        $returnInfo = [
            'code' => $code,
            'data' => $data,
        ];
        $fd         = $fd ?: $this->frame->fd;
        if ($fd) {
            //发送成功返回true，发送失败返回false
            return $this->distributor->connector->server->push($fd, json_encode($returnInfo), $opCode, $finish);
        } else {
            throw new \Exception('链接不存在');
        }
    }
}