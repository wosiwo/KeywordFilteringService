<?php
namespace Swoole;

class ArrayObject implements \ArrayAccess, \Serializable, \Countable, \Iterator
{
    protected $array;
    protected $index = 0;

    function __construct($array)
    {
        $this->array = $array;
    }

    function current()
    {
        return current($this->array);
    }

    function key()
    {
        return key($this->array);
    }

    function valid()
    {
        return count($this->array) >= $this->index;
    }

    function rewind()
    {
        $this->index = 0;
        return reset($this->array);
    }

    function next()
    {
        $this->index++;
        return next($this->array);
    }

    function serialize()
    {
        return serialize($this->array);
    }

    function unserialize($str)
    {
        $this->array = unserialize($str);
    }

    function offsetGet($k)
    {
        return $this->array[$k];
    }

    function offsetSet($k, $v)
    {
        $this->array[$k] = $v;
    }

    function offsetUnset($k)
    {
        unset($this->array[$k]);
    }

    function offsetExists($k)
    {
        return isset($this->array[$k]);
    }

    function contains($val)
    {
        return in_array($val, $this->array);
    }

    function join($str)
    {
        return new StringObject(implode($str, $this->array));
    }

    function insert($offset, $val)
    {
        if ($offset > count($this->array))
        {
            return false;
        }
        return array_splice($this->array, $offset, 0, $val);
    }

    function search($find)
    {
        return array_search($find, $this->array);
    }

    function count()
    {
        return count($this->array);
    }

    function append($val)
    {
        return array_push($this->array, $val);
    }

    function prepend($val)
    {
        return array_unshift($this->array, $val);
    }

    function slice($offset, $lenth = null)
    {
        return new ArrayObject(array_slice($this->array, $offset, $lenth));
    }

    function rand()
    {
        return $this->array[array_rand($this->array, 1)];
    }

    function toArray()
    {
        return $this->array;
    }
}
