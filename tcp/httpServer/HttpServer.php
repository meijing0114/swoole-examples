<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/2/11
 * Time: 下午3:50.
 */

require_once "./Request.php";
require_once "./Response.php";
require_once "./EndpointF.php";
require_once "./NetworkHelper.php";

class HttpServer
{
    protected $sw;

    protected $host = '0.0.0.0';
    protected $port = '28893';
    protected $worker_num = 4;
    protected $servType = 'http';

    protected $application = 'SWOOLE';
    protected $serverName = 'HTTPTEST';

    public function start()
    {
        $swooleServerName =  '\swoole_http_server';

        $sw = new $swooleServerName($this->host, $this->port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->sw = $sw;
        $this->sw->servType = $this->servType;

        $this->sw->on('Request', array($this, 'onRequest'));

        $setting = [
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

    public function onRequest($request, $response)
    {
        $req = new Request();
        $req->data = get_object_vars($request);



/* 加上这段对tars主控的请求,就会出现coredump
 *         $helper1 = new NetworkHelper();


        $registry = $this->packRegistry();
        $result =  $helper1->swooleCoroutineTcp("172.16.0.161",17890,$registry);
        $data = $result[0];
        // 接下来解码
        $decodeRet = \TUPAPI::decode($data, 3);

        $sBuffer = $decodeRet['sBuffer'];
file_put_contents("/data/ted/swoole_test/tcp/data",$data);
        $endpoint = \TUPAPI::getVector('',
            new \TARS_Vector(new EndpointF()), $sBuffer, false, 3);

        error_log( "endpoint:".var_export($endpoint,true));
*/
        $buf = "test";

        $len = 4 + strlen($buf);

        $iHeaderLen = pack('N',$len);

        $requestBuf = $iHeaderLen.$buf;

        error_log("fist time start\n");

        $respBuf1 =  $this->swooleCoroutineTcp("172.16.0.161",28891,$requestBuf)[1];

        /**
         * 强行插入了扩展解包逻辑,会导致下一次协程connect卡住
         */
        //$data = file_get_contents("/data/ted/swoole_test/tcp/data");
        //$decodeRet = \TUPAPI::decode($data, 3);
        //$sBuffer = $decodeRet['sBuffer'];
        //$endpoint = \TUPAPI::getVector('',
        //    new \TARS_Vector(new EndpointF()), $sBuffer, false, 3);

        // 这里尝试了把上面扩展的代码注释掉,
        // 如果$endpoint变量未定义,也会卡住,所以如果变量的定义不是在php空间内,打印这个变量,协程下一次就会卡住?

        error_log( "endpoint:".var_export($endpoint,true));

        error_log( "fist time end\n");

        error_log( "second time start\n");

        $respBuf2 = $this->swooleCoroutineTcp("172.16.0.161",28892,$requestBuf)[1];

        error_log( "second time end\n");


        $resp = new Response();
        $resp->servType = $this->servType;
        $resp->resource = $response;
        $resp->server = $this->sw;

        $resp->send($respBuf1."...".$respBuf2);

    }
    private function swooleCoroutineTcp($sIp, $iPort, $requestBuf, $timeout = 2)
    {
        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);

        $client->set(array(
            'open_length_check' => 1,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 0,       //第几个字节开始计算长度
            'package_max_length' => 2000000,  //协议最大长度
        ));
error_log("this before connect");
        if (!$client->connect($sIp, $iPort, $timeout)) {

            error_log( "socket connect failed\n");
        }
error_log("this after connect");
        if (!$client->send($requestBuf)) {
            $client->close();
            error_log( "socket send failed\n");
            return "";
        }

        //读取最多32M的数据
        $data = $client->recv();

        if (empty($data)) {
            $client->close();
            error_log( "socket rspbuf empty\n");
            return "";
        }

        $list = unpack('Nlen', substr($data, 0, 4));
        $packLen = $list['len'];
        $responseBuf = substr($data, 4, $packLen - 4);

        return [
            $data,
            $responseBuf
        ];
    }

    private function packRegistry() {
        $encodeBufs = [];

        $buffer = self::putString('id', 1, "PHPTest.PHPServer.obj", 3);
        $encodeBufs['id'] = $buffer;

        $requestBuf = \TUPAPI::encode(3, 1,
            'tars.tarsregistry.QueryObj', 'findObjectById', 0,
            0, 2000, [],
            [], $encodeBufs);

        return $requestBuf;

    }

    public static function putString($paramName, $tag, $string, $iVersion)
    {
            if ($iVersion === 1) {
                $buffer = \TUPAPI::putString($tag, $string, $iVersion);
            } else {
                $buffer = \TUPAPI::putString($paramName, $string, $iVersion);
            }

            return $buffer;
    }

}
