<?php
include '../../vendor/autoload.php';

\zxzgit\swd\test\service\UserService::generalFakeUser();


\zxzgit\swd\WebSocketApp::run([
    'debugOn' => true,
    'moduleList' => [
        'test' => \zxzgit\swd\test\modules\test\MessageModule::class,
    ],
    'messageDistributor' => \zxzgit\swd\test\MessageDistributor::class,

    'event' => [
        'initConnector' => function () {

        },
        'workerStart' => function (&$server, $id) {
            //echo PHP_EOL . "workerStart event" . PHP_EOL;
        },
        'open' => function (&$server, &$req) {
            //echo PHP_EOL . "open event" . PHP_EOL;
        },
        'beforeMessage' => function (&$server, &$frame) {
            //echo PHP_EOL . "beforeMessage event" . PHP_EOL;
        },
        'afterMessage' => function (&$server, &$frame) {
            //echo PHP_EOL . "afterMessage event" . PHP_EOL;
        },
        'close' => function (&$server, $fd) {
            echo PHP_EOL . "close event- 链接 $fd 关闭" . PHP_EOL;
        },
    ],

    /*
    'serverSetConfig' => [//https://wiki.swoole.com/wiki/page/13.html
        'worker_num' => 4,    //worker process num
        'reactor_num' => 2, //reactor thread num
        'worker_num' => 4,    //worker process num
        'backlog' => 128,   //listen backlog
        'max_request' => 50,
        'dispatch_mode' => 1,
        'max_conn' => 1000,
    ],
    */
]);