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
                $connector->debugOutput('fork child process fail');
            } elseif ($pid == 0) {
                self::distributor($connector, $frame);

                //子进程结束
                $connector->server->stop();
            } else {

            }
        } else {
            self::distributor($connector, $frame);
        }
    }

    /**
     * 信息分发处理
     * @param ConnectHandler $connector
     * @param $frame
     */
    static function distributor(&$connector, &$frame)
    {
        try {
            //信息处理
            $connector->debugOn && $connector->debugOutput(implode(PHP_EOL, [
                "链接{$frame->fd}-当前内存使用量：" . memory_get_usage(true) . " byte",
                "链接{$frame->fd}-当前子进程pid：" . posix_getpid(),
                "链接{$frame->fd}-收到的信息: {$frame->data}",
            ]));

            //消息分发器构建
            $messageDistributor = $connector->messageDistributor;
            /** @var MessageDistributor $distributor */
            $distributor = new $messageDistributor($connector, $frame, $frame->data);
            $distributor->run();

            //发送信息后事件处理
            $connector->triggerEvent('afterMessage', [&$connector->server, &$frame]);
        } catch (\Exception $exception) {
            throw  $exception;
            //$connector->debugOutput("信息分发错误，错误信息：" . $exception->getMessage() . PHP_EOL);
        }
    }
}
