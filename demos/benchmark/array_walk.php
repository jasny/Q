<?php

/**
 * This script is used to benchmark array_walk vs array_map vs a loop.
 * 
 * Conclusion:
 *  Using a simple loop is a lot faster
 *  Calling array_map and array_walk often resulted in a memory exception
 * 
 * Run this script with Zend profiler or Xdebug profiler. I no longer support poor man's profiling.
 * 
 * @ignore
 */

define('LOOP', 100);

function mapCallback($value)
{
	return $value + 2;
}

function walkCallback(&$value)
{
	$value += 2;
}


function wasteTime()
{
    for ($i=0; $i<LOOP; $i++);
}

function useLoop()
{
	$array = array_fill(0, 100, 1);
	
    for ($i=0; $i<LOOP; $i++) {
    	foreach ($array as &$v) $v += 2;
    }
}

function useMapCallback()
{
	$array = array_fill(0, 100, 1);
    for ($i=0; $i<LOOP; $i++) $array = array_map('mapCallback', $array);
}

function useMapClosure()
{
	$array = array_fill(0, 100, 1);
    for ($i=0; $i<LOOP; $i++) $array = array_map(function($value) {return $value + 2;}, $array);
}

function useWalkCallback()
{
    for ($i=0; $i<LOOP; $i++) {
    	$array = array_fill(0, 100, 1);
    	array_walk($array, 'walkCallback');
    }
}

function useWalkClosure()
{
	for ($i=0; $i<LOOP; $i++) {
		$array = array_fill(0, 100, 1);
		array_walk($array, function(&$value) {return $value + 2;});
	}
}

wasteTime();
useLoop();
useMapCallback();
useMapClosure();
useWalkCallback();
useWalkClosure();
