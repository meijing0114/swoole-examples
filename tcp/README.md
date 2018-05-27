

## httpServer
http 测试服务

尝试请求一次TCPServer1

再请求一次tcpServer2

在HttpServer.php的167行,是导致下一次协程卡住的原因。具体见代码。

另外,如果把请求tcpServer,换成请求tars的主控registry服务,就会出现coredump,见tcp文件夹下的core-php-19884_1527407304.tar.gz,
导致这个的原因还需要再比较一下两边的不同才能下定论。


## tcpServer1
测试服务1

## tcpServer2
测试服务2


## tars-extension
负责打包解包的扩展,应该无关。

## core文件
另外一个core文件, 就是我们在压测tars服务的时候出现的:
core-php-29691_1527400741.tar.gz

## 运行环境
php-7.1.7

### swoole

swoole support => enabled
Version => 2.1.2
Author => tianfeng.han[email: mikan.tenny@gmail.com]
coroutine => enabled
epoll => enabled
eventfd => enabled
timerfd => enabled
signalfd => enabled
cpu affinity => enabled
spinlock => enabled
rwlock => enabled
async redis client => enabled
async http/websocket client => enabled
openssl => enabled
Linux Native AIO => enabled
pcre => enabled
zlib => enabled
mutex_timedlock => enabled
pthread_barrier => enabled
futex => enabled

Directive => Local Value => Master Value
swoole.aio_thread_num => 2 => 2
swoole.display_errors => On => On
swoole.use_namespace => On => On
swoole.use_shortname => On => On
swoole.fast_serialize => Off => Off
swoole.unixsock_buffer_size => 8388608 => 8388608