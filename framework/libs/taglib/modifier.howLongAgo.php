<?php
function smarty_modifier_howLongAgo($string)
{
    return Swoole\Tool::howLongAgo($string);
}
