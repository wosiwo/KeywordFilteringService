<?php
$event['master'] = array(
    'type' => Swoole\Queue\Redis::class,
    'async' => true,
);
return $event;