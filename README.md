# swoole-websocket-distributor
基于 swoole 的 WebSocket\Server 内容分发，可以将接受的信息分发到模块和控制器。

#基本用法
 
基本构建使用参考test目录：
```
--testapp
  --controllers
    --IndexController.php
    --OtherController.php
  --modules
    --test  //test module name
       --controllers
         --IndexController.php
         --OtherController.php
       --MessageModule.php
  --public
    --indexTest.php
  --MessageDistributor.php
```

public/indexTest.php
```
\zxzgit\swd\zxzgit\swdApp::run([
    'moduleList' => [
        'test' => \testapp\test\modules\test\MessageModule::class,
    ],
    'messageDistributor' => \testapp\test\MessageDistributor::class,
    'event' => [
        'initConnector' => function () {

        },
        'workerStart' => function (&$server, $id) {
            echo PHP_EOL . "workerStart event" . PHP_EOL;
        },
        'open' => function (&$server, &$req) {
            echo PHP_EOL . "open event" . PHP_EOL;
        },
        'beforeMessage' => function (&$server, &$frame) {
            echo PHP_EOL . "beforeMessage event" . PHP_EOL;
        },
        'afterMessage' => function (&$server, &$frame) {
            echo PHP_EOL . "afterMessage event" . PHP_EOL;
        },
        'close' => function (&$server, $fd) {
            echo PHP_EOL . "afterMessage event" . PHP_EOL;
        },
    ]
]);
 ```

controllers/IndexController.php

```
class IndexController extends \zxzgit\swd\libs\AbstractController {}
```

MessageDistributor.php

```
class MessageDistributor extends \zxzgit\swd\libs\MessageDistributor{
    public $moduleList = [
        //'test' => \zxzgit\swd\test\modules\test\MessageModule::class,
    ];
}
```

modules/test/MessageModule.php

```
class MessageModule extends \zxzgit\swd\libs\MessageModule{}
```

