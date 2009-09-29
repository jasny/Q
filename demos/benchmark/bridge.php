<?php

/**
 * This script is used benchmark the time needed to use the bridge pattern.
 * 
 * Conclusion:
 *   Using a bridge makes calling a method 2 times as slow.
 *   Using a call bridge makes calling a method 3 times as slow.
 *   A static bridge performs about the same as a normal bridge.
 * 
 * Run this script with Zend profiler or Xdebug profiler. I no longer support poor man's profiling.
 * 
 * @ignore
 */

define('LOOP', 10000);

class Methods_A
{
	function x()
	{}

	static function sx()
	{}
}

class Methods_B
{
	function y()
	{}

	static function sy()
	{}
}

class NoBridge
{
	function x()
	{}
	
	function y()
	{}
}

class Bridge
{
	protected $a;
	protected $b;
	
	public function __construct()
	{
		$this->a = new Methods_A();
		$this->b = new Methods_B();
	}

	function x()
	{
		$this->a->x();
	}
	
	function y()
	{
		$this->b->y();
	}
}

class CallBridge
{
	protected $a;
	protected $b;
	
	public function __construct()
	{
		$this->a = new Methods_A();
		$this->b = new Methods_B();
	}
		
	public function __call($method, $args)
	{
		if (method_exists($this->a, $method)) {
			return call_user_func_array(array($this->a, $method), $args);
		} elseif (method_exists($this->b, $method)) {
			return call_user_func_array(array($this->b, $method), $args);
		}
	}
}

class StaticBridge
{
	function sx()
	{
		Methods_A::sx();
	}
	
	function sy()
	{
		Methods_B::sy();
	}
}

class StaticCallBridge
{
	public function __call($method, $args)
	{
		if (method_exists('Methods_A', $method)) {
			call_user_func_array(array('Methods_A', $method), $args);
		} elseif (method_exists('Methods_B', $method)) {
			call_user_func_array(array('Methods_B', $method), $args);
		}
	}
}

function once_NoBridge()
{
	$o = new NoBridge();
	
    for ($i=0; $i<LOOP; $i++) {
    	$o->x();
    	$o->y();
    }
}

function once_Bridge()
{
	$o = new Bridge();
	
    for ($i=0; $i<LOOP; $i++) {
    	$o->x();
    	$o->y();
    }
}
function once_CallBridge()
{
	$o = new CallBridge();
	
    for ($i=0; $i<LOOP; $i++) {
    	$o->x();
    	$o->y();
    }
}

function once_StaticBridge()
{
	$o = new StaticBridge();
	
    for ($i=0; $i<LOOP; $i++) {
    	$o->sx();
    	$o->sy();
    }
}
function once_StaticCallBridge()
{
	$o = new StaticCallBridge();
	
    for ($i=0; $i<LOOP; $i++) {
    	$o->sx();
    	$o->sy();
    }
}

function often_NoBridge()
{
	$o = new NoBridge();
	
	for ($i=0,$m=LOOP/100; $i<$m; $i++) {
    	for ($j=0; $j<100; $j++) {
	    	$o->x();
	    	$o->y();
    	}
    }
}

function often_Bridge()
{
	$o = new Bridge();
	
	for ($i=0,$m=LOOP/100; $i<$m; $i++) {
    	for ($j=0; $j<100; $j++) {
	    	$o->x();
	    	$o->y();
    	}
    }
}

function often_CallBridge()
{
	$o = new CallBridge();
	
    for ($i=0,$m=LOOP/100; $i<$m; $i++) {
    	for ($j=0; $j<100; $j++) {
	    	$o->x();
	    	$o->y();
    	}
    }
}

function often_StaticBridge()
{
	$o = new StaticBridge();
	
    for ($i=0,$m=LOOP/100; $i<$m; $i++) {
    	for ($j=0; $j<100; $j++) {
	    	$o->sx();
	    	$o->sy();
    	}
    }
}

function often_StaticCallBridge()
{
	$o = new StaticCallBridge();
	
    for ($i=0,$m=LOOP/100; $i<$m; $i++) {
    	for ($j=0; $j<100; $j++) {
	    	$o->sx();
	    	$o->sy();
    	}
    }
}

once_NoBridge();
once_Bridge();
once_CallBridge();
once_StaticBridge();
once_StaticCallBridge();

often_NoBridge();
often_Bridge();
often_CallBridge();
often_StaticBridge();
often_StaticCallBridge();
