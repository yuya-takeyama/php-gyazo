<?php
require_once 'Slim/Slim.php';

class Phat_Application extends Slim implements ArrayAccess
{
    private $container = array();

    public function offsetSet($key, $value)
    {
        $this->container[$key] = $value;
    }

    public function offsetGet($key)
    {
        return $this->container[$key];
    }

    public function offsetExists($key)
    {
        return isset($this->container[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->container[$key]);
    }
}
