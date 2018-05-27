<?php
/**
 * Created by PhpStorm.
 * User: yuanyizhi
 * Date: 15/8/12
 * Time: 下午2:40.
 */


class Response
{
    public $fd;
    public $fromFd;
    public $server;
    public $rspBuf;
    public $servType;
    public $resource;

    public function send($data)
    {
        $this->server->send($this->fd, $data);

    }
}
