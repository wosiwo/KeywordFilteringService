<?php
if (PHP_OS == 'WINNT')
{
    return new \Swoole\Platform\Windows();
}
else
{
    return new \Swoole\Platform\Linux();
}