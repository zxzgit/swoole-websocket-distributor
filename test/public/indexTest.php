<?php
include '../../vendor/autoload.php';

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
            //echo PHP_EOL . "afterMessage event" . PHP_EOL;
        },
    ]
]);