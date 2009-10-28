<?php

/**
 * This script is used benchmark executing on a preg_replace.
 * 
 * Conclusion:
 *  Using the -e flag is 2x as slow as using preg_replace_callback
 *  Using a closure is only slightly slower (within margin) of using a registered function
 * 
 * Run this script with Zend profiler or Xdebug profiler. I no longer support poor man's profiling.
 * 
 * @ignore
 */

define('LOOP', 10000);


function wasteTime()
{
    for ($i=0; $i<LOOP; $i++);
}

function useExec()
{
    for ($i=0; $i<LOOP; $i++) preg_replace('/\b[a-z]\w++/e', "strtoupper('\\0')", 'abc 10def test');
}

function callback($matches)
{
	return strtoupper($matches[0]);
}

function useCallback()
{
    for ($i=0; $i<LOOP; $i++) preg_replace_callback('/\b[a-z]\w+/', 'callback', 'abc 10def test');
}

function useClosure()
{
    for ($i=0; $i<LOOP; $i++) preg_replace_callback('/\b[a-z]\w+/', function ($matches) {return strtoupper($matches[0]);}, 'abc 10def test');
}

wasteTime();
useExec();
useCallback();
useClosure();
