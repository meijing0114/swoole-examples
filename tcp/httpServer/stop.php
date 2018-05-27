<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/5/27
 * Time: 下午12:03
 */

$name = "SWOOLE.HTTPTEST";

$ret = getProcess($name);

if ($ret['exist'] === false) {
    echo "{$name} stop  \033[34;40m [FAIL] \033[0m process not exists".PHP_EOL;

    return;
}

$pidList = implode(' ', $ret['pidList']);
$cmd = "kill -9 {$pidList}";
exec($cmd, $output, $r);

if ($r === false) { // kill失败时
    echo "{$name} stop  \033[34;40m [FAIL] \033[0m posix exec fail".PHP_EOL;
    exit;
}

echo "{$name} stop  \033[32;40m [SUCCESS] \033[0m".PHP_EOL;


function getProcess($processName)
{
    $cmd = "ps aux | grep '".$processName."' | grep master | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    $cmd = "ps aux | grep '".$processName."' | grep manager | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    $cmd = "ps aux | grep '".$processName."' | grep worker | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    $cmd = "ps aux | grep '".$processName."' | grep task | grep -v grep  | awk '{ print $2}'";
    exec($cmd, $ret);

    if (empty($ret)) {
        return [
            'exist' => false,
        ];
    } else {
        return [
            'exist' => true,
            'pidList' => $ret,
        ];
    }
}