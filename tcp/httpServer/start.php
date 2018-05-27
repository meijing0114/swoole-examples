<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/5/27
 * Time: 上午10:43
 */

require_once "./HttpServer.php";


$server = new HttpServer();
$server->start();