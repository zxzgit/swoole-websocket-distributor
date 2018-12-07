<?php

namespace zxzgit\swd\libs;


class MessageHandler
{
    /**
     * 用户信息处理
     * @param ConnectHandler $connector
     * @param $frame
     * @param bool $isDoFork
     */
    static public function msgDeal(&$connector, &$frame, $isDoFork = true)
    {
        declare(ticks = 1);
        //清除子进程结束后的僵尸进程的生成，pcntl_signal(SIGCHLD, SIG_IGN)通知内核，自己对子进程的结束不感兴趣，那么子进程结束后，内核会回收，并不再给父进程发送信号
        pcntl_signal(SIGCHLD, SIG_IGN);

        if ($isDoFork) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                //echo 'could not fork生成子进程失败';
            } elseif ($pid == 0) {
                echo '当前内存使用量：' . memory_get_usage(true) . PHP_EOL;
                echo '当前子进程pid：' . posix_getpid() . PHP_EOL;

                self::distributor($connector, $frame);

                //子进程结束
                $connector->server->stop();
            } else {
                //echo "I'm the parent process 子进程的pid值：{$pid} \n";
            }
        } else {
            self::distributor($connector, $frame);
        }
    }

    static function parseData($frameData)
    {
        return $receiveInfo = json_decode($frameData, true);
    }

    /**
     * 信息分发处理
     * @param $connector
     * @param $frame
     */
    static function distributor(&$connector, &$frame)
    {
        try {
            echo str_repeat('=', 20) . PHP_EOL;

            //信息处理
            echo "收到的信息: {$frame->data}" . PHP_EOL;
            echo "链接id: {$frame->fd}" . PHP_EOL;

            //消息分发器构建
            $messageDistributor = $connector->messageDistributor;
            /** @var MessageDistributor $distributor */
            $distributor = new $messageDistributor($connector, $frame, $frame->data);
            $distributor->run();

            echo str_repeat('=', 20) . PHP_EOL;

            //发送信息后事件处理
            $connector->triggerEvent('afterMessage', [&$connector->server, &$frame]);
        } catch (\Exception $exception) {
            echo "信息分发错误，错误信息：" . $exception->getMessage() . PHP_EOL;
        }
    }
}
