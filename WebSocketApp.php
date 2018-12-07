<?php
/**
 * Created by PhpStorm.
 * User: zxz
 * Date: 2018/11/22
 * Time: 下午10:02
 */

namespace zxzgit\swd;


use zxzgit\swd\libs\ConnectHandler;

class WebSocketApp
{

    /**
     * @var ConnectHandler $connector
     */
    static $connector;

    public static function run(array $config = [])
    {
        self::$connector = new ConnectHandler($config);
        self::$connector->run();
    }
}