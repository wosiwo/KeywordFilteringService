<?php
$captcha = array(


    'default_max_times' => 5,//服务默认 每天可以创建最个数
    'default_error_times' => 5,//服务默认
    'default_interval' => 60,//默认间隔时间
    'error_code' => array(
        0 => 'success',
        400 => 'params error',
        900 => 'server error',
        901 => 'please try after interval ',
        902 => 'exceed max time ',
        903 => 'phone and captcha empty ',
        904 => 'exceed error times ',
        905 => 'has been expired ',
        906 => 'captcha not match ',
        907 => 'captcha was used ',
        908 => 'captcha was not exists ',
    ),

);
return $captcha;
