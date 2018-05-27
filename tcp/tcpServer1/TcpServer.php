<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/2/11
 * Time: 下午3:50.
 */

require_once "./Request.php";
require_once "./Response.php";

class TcpServer
{
    protected $sw;

    protected $host = '0.0.0.0';
    protected $port = '28891';
    protected $worker_num = 4;
    protected $servType = 'tcp';

    protected $application = 'SWOOLE';
    protected $serverName = 'TCPTEST1';

    public function start()
    {
        $swooleServerName =  '\swoole_server';

        $sw = new $swooleServerName($this->host, $this->port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->sw = $sw;
        $this->sw->servType = $this->servType;


        $setting = [
            //包头定义数据长度
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 0,       //第几个字节开始计算长度
            'package_max_length' => 2000000,  //协议最大长度
            'worker_num' => $this->worker_num,
            'daemonize' => true,
            'backlog' => 128,
            'log_level' => 0
        ];

        $this->sw->set($setting);

        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('ManagerStart', array($this, 'onManagerStart'));
        $this->sw->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->sw->on('Connect', array($this, 'onConnect'));
        $this->sw->on('Receive', array($this, 'onReceive'));
        $this->sw->on('Close', array($this, 'onClose'));
        $this->sw->on('WorkerStop', array($this, 'onWorkerStop'));

        $this->sw->start();
    }

    public function stop()
    {
    }

    public function restart()
    {
    }

    public function reload()
    {
    }

    private function _setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } elseif (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            trigger_error(__METHOD__.' failed. require cli_set_process_title or swoole_set_process_name.');
        }
    }

    public function onMasterStart($server)
    {
        $this->_setProcessName($this->application.'.'.$this->serverName.': master process');
        file_put_contents("./master_pid", $server->master_pid);
        file_put_contents("./manager_pid", $server->manager_pid);

    }

    public function onManagerStart($server)
    {
        // rename manager process
        $this->_setProcessName($this->application.'.'.$this->serverName.': manager process');
    }

    public function onWorkerStart($server, $workerId)
    {
        if ($workerId >= $this->worker_num) {
            $this->_setProcessName($this->application.'.'.$this->serverName.': task worker process');
        } else {
            $this->_setProcessName($this->application.'.'.$this->serverName.': event worker process');
        }
    }

    public function onConnect($server, $fd, $fromId)
    {
    }

    public function onClose($server, $fd, $fromId)
    {
    }

    public function onWorkerStop($server, $workerId)
    {
    }

    public function onTimer($server, $interval)
    {
    }

    // 这里应该找到对应的解码协议类型,执行解码,并在收到逻辑处理回复后,进行编码和发送数据 todo
    public function onReceive($server, $fd, $fromId, $data)
    {
        $list = unpack('Nlen', substr($data, 0, 4));
        $packLen = $list['len'];
        $requestBuf = substr($data, 4, $packLen - 4);

        error_log("received:" . $requestBuf);

        $buf = "success";
        $length = 4 + strlen($buf);
        $headerLen = pack('N',$length);

        $rspBuf = $headerLen.$buf;

        $response = new Response();
        $response->fd = $fd;
        $response->fromFd = $fromId;
        $response->server = $server;

        $response->send($rspBuf);
    }
}
