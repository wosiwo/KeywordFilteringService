KeywordFilteringService
=====

* A PHP HTTPServer that you can use to filter keywords. It depons on a php extension named Swoole.

* 以HTTP方式提供关键词过滤服务，这个服务依赖php swoole扩展。
* swoole扩展让PHP有了直接提供HTTP服务的能力，使得庞大的字典可以常驻内存，避免原生php每次访问都需要重新载入字典所耗费的时间（随着字典条目的增加这个时间会越来越长）。
    
    >时间主要耗费在将字符串反序列化成数组上,这是由于php数组在内核的实现机制
* 这里使用HTTP方式的原因主要是考虑到通用性，可以参考swoole官方的example修改成socket接口来实现同样的功能。
* 具体的过滤实现代码则是从phpwind中提取出来 

## Requirements

* php swoole extension
* swooleFramework
* phpwind filter class
* PHP 5.3.10 at least

## Usage
* step 1 install swoole extension
    
```bash
    pecl install swoole
    vim /etc/php.ini 
    #add extension=swoole.so  
```
* step 2 dowload KeywordFilteringServie
```bash
git clone git@github.com:wosiwo/KeywordFilteringService.git
```
* step 3 create binary dict 

    >this step will read original dict from /server/dict_all.txt ,then convert Plaintext into ASCII code and use it to  build array with a struct which is conducive to search 
```bash
cd KeywordFilteringService/dict
php createBinaryDict.php
```
* step 4 start the httpserver
```bash
php server.php start
```
* step 5 now you can call it by service client
    
    >php tests/keyword_service.php


    >if the page show a [["wosiwo","0.8"]] , it shows that the service is running successfully.


##Tip:
> To increase the transmission limit ,You may need to change your kernel parameters of your computer.
>In FreeBSD/MacOS:

```bash
sysctl -w net.local.dgram.maxdgram=8192
sysctl -w net.local.dgram.recvspace=200000 修改Unix Socket的buffer区尺寸
```

>other to see [http://wiki.swoole.com/wiki/page/11.html](http://wiki.swoole.com/wiki/page/11.html)
