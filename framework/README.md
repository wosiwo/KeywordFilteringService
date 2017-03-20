SwooleFramework: PHP的高级开发框架
----
与其他Web框架不同，SwooleFramework是一个全功能的后端服务器框架。除了Web方面的应用之外，更广泛的后端程序中都可以使用。

* 内置PHP应用服务器，可脱离nginx/php-fpm/apache独立运行
* 配置化与资源自动工厂，可实现从配置中创建资源对象，完全无需new对象
* 全面采用命名空间+autoload，代码中无需任何的include/require
* 全局注册树，所有资源都挂载到全局树上，彻底实现资源的单例管理和懒加载
* 全栈框架，提供了数据库操作，模板，Cache，日志，队列，上传管理，用户管理等几乎所有的功能
 
> PHP版本需求： PHP5.4/PHP5.5/PHP5.6/PHP7.0/PHP7.1，不支持PHP5.3

应用服务器
-----
使用内置应用服务器，可节省每次请求代码来的额外消耗。连接池技术可以很好的帮助存储系统节省连接资源。

###Swoole应用服务器支持的特性###
* 热部署，代码更新后即刻生效。依赖runkit扩展（ <https://github.com/zenovich/runkit> ）
* MaxRequest进程回收机制，防止内存泄露
* 支持使用Windows作为开发环境
* http KeepAlive，可节省tcp connect带来的开销
* 静态文件缓存，节省流量
* 支持Gzip压缩，节省流量
* 支持MySQL重新连接
* 支持文件上传
* 支持POST大文本
* 支持Session/Cookie
* 支持Http/FastCGI两种协议

###Swoole框架额外提供的网络协议###
* WebSocket协议支持，并附带一个基于websocket协议的webim系统
* 普通Web服务器，可支持静态文件和普通include php方式的程序
* SOA逻辑层服务器/客户端，支持并行请求
* 一个简单的SMTP服务器
* FtpServer
* 异步HttpClient

在线体验地址：<http://www.swoole.com/page/index/>

SwooleFramework应用服务器，需要安装swoole扩展。
```
pecl install swoole
```
然后修改php.ini加入extension=swoole.so
```php
<?php
require __DIR__.'/libs/lib_config.php';

$AppSvr = new Swoole\Protocol\AppServer();
$AppSvr->loadSetting(__DIR__."/swoole.ini"); //加载配置文件
$AppSvr->setAppPath(__DIR__.'/apps/'); //设置应用所在的目录
$AppSvr->setLogger(new Swoole\Log\EchoLog(false)); //Logger

/**
 *如果你没有安装swoole扩展，这里还可选择
 * BlockTCP 阻塞的TCP，支持windows平台，需要将worker_num设为1
 * SelectTCP 使用select做事件循环，支持windows平台，需要将worker_num设为1
 * EventTCP 使用libevent，需要安装libevent扩展
 */
$server = new \Swoole\Network\Server('0.0.0.0', 8888);
$server->setProtocol($AppSvr);
$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 1, 'max_request' => 5000));
```

```shell
php server.php
[2013-07-09 12:17:05]  Swoole. running. on 0.0.0.0:8888
```

在浏览器中打开 http://127.0.0.1:8888/

Nginx+FPM+Swoole框架的URLRewrite配置
-----
```shell
server {
    listen  80;
    server_name  www.swoole.com;
    root  /data/wwwroot/www.swoole.com;

    location / {
        if (!-e $request_filename){
            proxy_pass http://127.0.0.1:9501;
        }
    }
}
```

Apache+Swoole框架的URLRewrite配置
-----
```shell
<VirtualHost *:80>
    ServerName www.swoole.com
    DocumentRoot /data/webroot/www.swoole.com
    DirectoryIndex index.html index.php

    <Directory "/data/webroot/www.swoole.com">
        Options Indexes FollowSymLinks
            AllowOverride None
            Require all granted
    </Directory>

#   ProxyPass /admin !
#   ProxyPass /index.html !
#   ProxyPass /static !
#   ProxyPass / http://127.0.0.1:9501/

    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{DOCUMENT_ROOT}/%{REQUEST_FILENAME} !-f
        RewriteCond %{DOCUMENT_ROOT}/%{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ http://127.0.0.1:9501$1 [L,P]
    </IfModule>
</VirtualHost>
```

[压测数据](doc/bench.md)
