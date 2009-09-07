<?php

/**
 * This script is used benchmark the time needed to use instanceof vs the use of a stub.
 * 
 * Conclusion:
 *   Instanceof and isset are about as fast.
 *   Using method exists is ~25% slower.
 *   Calling a stub is twice as slow.
 * 
 * Run this script with Zend profiler or Xdebug profiler. I no longer support poor man's profiling.
 * 
 * @ignore
 */

define('LOOP', 100000);

interface Real
{
	public function doIt($info);
}

class Stub implements Real
{
	public function doIt($info)
	{}
}

class Mock
{}


function wasteTime()
{
    for ($i=0; $i<LOOP; $i++);
}

function checkIsset()
{
	$a = null;
	$info = array('test');
	
    for ($i=0; $i<LOOP; $i++) {
    	if (isset($a)) $a->doIt($info);
    }
}

function checkInstanceof()
{
	$a = new Mock();
	$info = array('test');
	
    for ($i=0; $i<LOOP; $i++) {
    	if ($a instanceof Real) $a->doIt($info);
    }
}

function checkNotInstanceof()
{
	$a = new Mock();
	$info = array('test');
	
    for ($i=0; $i<LOOP; $i++) {
    	if (!($a instanceof Mock)) $a->doIt($info);
    }
}

function checkMethodExists()
{
	$a = new Mock();
	$info = array('test');
	
    for ($i=0; $i<LOOP; $i++) {
    	if (method_exists($a, 'doIt')) $a->doIt($info);
    }
}

function useStub()
{
	$a = new Stub();
	$info = array('test');
	
    for ($i=0; $i<LOOP; $i++) $a->doIt($info);
}

wasteTime();
checkIsset();
checkInstanceof();
checkNotInstanceof();
checkMethodExists();
useStub();