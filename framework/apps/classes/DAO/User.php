<?php
namespace App\DAO;

/**
 * Class User
 * example: $user = new App\DAO\User(1);  $user->get();
 * @package App\DAO
 */
class User
{
    protected $id;
    function __construct($id)
    {
        $this->id = $id;
    }

    function get()
    {
        return model('User')->get($this->id);
    }
}
