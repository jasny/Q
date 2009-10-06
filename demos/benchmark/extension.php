<?php

/**
 * This script is used benchmark getting a file extension.
 * 
 * Conclusion:
 *   Using pathinfo() is about 6 as fast.
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

function usePathinfo()
{
	for ($i=0; $i<LOOP; $i++) pathinfo("/var/www/image.jpg", PATHINFO_EXTENSION);
}

function useExplode()
{
	for ($i=0; $i<LOOP; $i++) end(explode('.', "/var/www/image.jpg"));
}

wasteTime();
usePathinfo();
useExplode();
