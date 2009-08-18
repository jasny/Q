<?php
/**
 * This script is used benchmark the time needed to create an object.
 * Q uses the command pattern as well as value objects.
 * 
 * Conclusion:
 *   Creating a value object is ~50% more expensive than an assoc array.
 *   Doing `new StdClass()` takes 260% the time of casting array() to an object. *surprised*
 *   The number of methods don't matter.
 *   The number of properties without a default value don't matter either.
 *   Each property with a default value will add about 15% loading time.
 *     An object with 7 properties with default values will cost 2x the time to create.
 *     Never use `public $prop = null` or `private $props = array()`, this is a pure waste of time. 
 * 
 * Run this script with Zend profiler or Xdebug profiler. I no longer support poorman profiling.
 * 
 * @ignore
 */

define('LOOP', 100000);

/** */
class EmptyClass
{}

class PropClass
{
    public $p1;
    public $p2; 
    protected $p3; 
    protected $p4; 
    private $p5; 
    private $p6; 
}

class NullPropClass
{
    public $p1 = null;
    public $p2 = null; 
    protected $p3 = null; 
    protected $p4 = null; 
    private $p5 = null; 
    private $p6 = null; 
}

class DefPropClass
{
    public $p1 = 1;
    public $p2 = 2; 
    protected $p3 = 3; 
    protected $p4 = 4; 
    private $p5 = 5; 
    private $p6 = 6; 
}

class MethodClass
{
    function A()
    {}

    function B()
    {}

    function C()
    {}
}

class UserClass
{
    public $p1 = 1;
    
    function A()
    {}
}
    
function wasteTime()
{
    for ($i=0; $i<LOOP; $i++);
}

function createArrays()
{
    for ($i=0; $i<LOOP; $i++) $b = array();
}

function createAssocArrays()
{
    for ($i=0; $i<LOOP; $i++) $b = array('p1'=>1, 'p2'=>2, 'p3'=>3, 'p4'=>4, 'p5'=>5, 'p6'=>6);
}

function createStdObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new StdClass();
}

function createCastObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = (object)array();
}

function createValueObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = (object)array('p1'=>1, 'p2'=>2, 'p3'=>3, 'p4'=>4, 'p5'=>5, 'p6'=>6);
}

function createEmptyObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new EmptyClass();
}

function createPropObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new PropClass();
}

function createNullPropObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new NullPropClass();
}

function createDefPropObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new DefPropClass();
}

function createMethodObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new MethodClass();
}

function createUserObjects()
{
    for ($i=0; $i<LOOP; $i++) $b = new UserClass();
}

wasteTime();
createArrays();
createAssocArrays();
createStdObjects();
createCastObjects();
createValueObjects();
createEmptyObjects();
createPropObjects();
createNullPropObjects();
createDefPropObjects();
createMethodObjects();
createUserObjects();