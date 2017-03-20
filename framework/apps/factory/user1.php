<?php
class User1
{
    function __construct()
    {

    }
    function hello()
    {
        echo __METHOD__;
    }
}

return new User1();