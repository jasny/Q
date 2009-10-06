<?php

/**
 * This script is used benchmark getting properties of an object.
 * 
 * Conclusion:
 *  Using __get and __offsetGet is 6 time slower than accessing an existing property.
 *  Getting a property even with a magic function is quite fast, so it is probably not needed to optimize this.
 * 
 * Run this script with Zend profiler or Xdebug profiler. I no longer support poor man's profiling.
 * 
 * @ignore
 */

define('LOOP', 100000);

class Normal
{
	public $test = 10;
}

class Getter
{
	protected $props = array('test'=>10);
	
	public function __get($name)
	{
		return $this->props[$name];
	}
}

class GetArray extends ArrayObject
{}

class GetArrayRef extends ArrayObject
{
	public function __get($name)
	{
		if (isset($this[$name])) $this->$name =& $this[$name];
		return $this->$name;
	}
}

class GetOffset implements ArrayAccess
{
	protected $props = array('test'=>10);
	
	public function offsetGet($name)
	{
		return $this->props[$name];
	}

	public function offsetExists($name)
	{
		return isset($this->props[$name]);
	}
	
	public function offsetSet($name, $value)
	{}

	public function offsetUnset($name)
	{}
}

function wasteTime()
{
    for ($i=0; $i<LOOP; $i++);
}

function useStdClass()
{
	$ob = (object)array('test'=>10);
    for ($i=0; $i<LOOP; $i++) $ob->test;
}

function useNormal()
{
	$ob = new Normal();
    for ($i=0; $i<LOOP; $i++) $ob->test;
}

function useGetter()
{
	$ob = new Getter();
	for ($i=0; $i<LOOP; $i++) $ob->test;
}

function useGetArray()
{
	$ob = new GetArray();
	$ob['test'] = 10;
	for ($i=0; $i<LOOP; $i++) $ob['test'];
}

function useGetArrayRef()
{
	$ob = new GetArrayRef();
	$ob['test'] = 10;
	for ($i=0; $i<LOOP; $i++) $ob->test;
}

function useGetOffset()
{
	$ob = new GetOffset();
	for ($i=0; $i<LOOP; $i++) $ob['test'];
}

wasteTime();
useStdClass();
useNormal();
useGetter();
useGetArray();
useGetArrayRef();
useGetOffset();
