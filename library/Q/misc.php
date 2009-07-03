<?php
namespace Q;

// ------- Class -------

/**
 * Return true if $class is or extends $base_class.
 *
 * @param string $class
 * @param string $base_class
 * @return boolean
 */
function class_is_a($class, $base_class)
{
	if (!class_exists($base_class, false) && !interface_exists($base_class, false)) return false;
	if ($class === $base_class) return true;

	$o = unserialize('O:'. strlen($class) . ':"' . $class . '":0:{}');
	return $o instanceof $base_class;
}

/**
 * Include a file to load a class.
 *
 * @param string $class  Classname (in global namespace)
 * @return boolean
 */
function load_class($class)
{
	if ($class[0] == '\\') {
		trigger_error("The class '{$class}' should be specified without the '\\' at the beginning.", E_USER_WARNING);
		$class = substr($class, 0, 2);
	}
	
	if (class_exists($class, false)) return true;

	if (!preg_match('/^[a-z_]\w*(?:\\\\[a-z_]\w*)?$/i', $class)) throw new SecurityException("Illegal load file for class '$class': Illigal classname. Is someone trying to hack?");
	
	include_once(str_replace(array('_', '\\'), '/', $class) . ".php");
	return class_exists($class, false);
}

// -------- String -------

/**
 * Remove the first and last character if they are specified in the character list
 *
 * @param string $value
 * @param string $charlist
 * @return array
 */
function unquote($value, $charlist='\'"`')
{
	$matches = null;
	return preg_match('/^([' . preg_quote($charlist, '/') . '])(.*)\\1$/', trim($value), $matches) ? stripcslashes($matches[2]) : $value;
}

/**
 * Parses the key into global variable and sets it with the value.
 *
 * @param string        $key
 * @param mixed         $value
 * @param array|object  $target  If $target is present, variables are stored in this variable as array elements / properties instead.    
 */
function parse_key($key, $value, &$target=null)
{
    $matches = null;
    if (!preg_match_all('/(?<=^|\[)[^\[\]]*/', $key, $matches)) {  // Not a really good test for validation
        trigger_error("Unable to parse key into variable: Invalid key '$key'.", E_USER_WARNING);
        return;
    }
    
    if (isset($target)) $var =& $target;
      else $var =& $GLOBALS;
    
    $k = array_shift($matches[0]);
    if (is_object($var)) $var =& $var->$k;
     else $var =& $var[$k]; 
    
    foreach ($matches[0] as $k) {
        if (empty($k)) {
            $var[] = null;
            end($var);
            $k = key($var);
        } else {
            $k = unquote($k);
            if (!isset($var[$k])) $var[$k] = null;
        }
        $var =& $var[$k];
    }
    
    $var = $value;
}

/**
 * Split a string on $seperator, grouping values between quotes and round brackets
 *
 * @param string $string
 * @param string $seperator  Character list; Split on any character in $seperator. With .. you can specify a range of characters.
 * @param string $unquote    Character list; Trim these characters for each part. TRUE: remove ' and ";
 * @return array
 */
function split_set($string, $seperator=";", $unquote=true)
{
	if (!is_scalar($string) || empty($string)) return (array)$string;
	
	$matches = null;
	$seperator = str_replace('\\.\\.', '-', preg_quote($seperator));
	preg_match_all('/(?:(?>`[^`]*`)|(?>"(?:\\\\"|[^"])*")|(?>\'(?:\\\\\'|[^\'])*\')|\((?:(?R)|[' . $seperator . '])*\)|(?>[^`"\'()' . $seperator . ']+))+/', $string, $matches);
	
	$parts = array_map('trim', $matches[0]);
	if (!$unquote) return $parts;

	if ($unquote === true) $unquote = '\'"';
	foreach ($parts as &$value) $value = unquote($value, $unquote);
	return $parts;
}

/**
 * Split a string on $seperator as key=value, grouping values between quotes and round brackets.
 *
 * @param string $string
 * @param string $seperator  Character list; Split on any character in $seperator. With .. you can specify a range of characters.
 * @param string $unquote    Character list; Trim these characters for each part. TRUE: remove ' and ";
 * @return array
 * 
 * @todo Combine with split_set
 */
function split_set_assoc($string, $seperator=";", $unquote=true)
{
	if (!is_scalar($string) || empty($string)) return $string;
	
	$matches = null;
	$seperator = str_replace('\\.\\.', '-', preg_quote($seperator));
	if ($unquote === true) $unquote = '\'"';
	
	$str = "";
	$values = array();
	
	preg_match_all('/(?:([^' . $seperator . '=]+)\s*\=)?((?:(?>`[^`]*`)|(?>"(?:\\\\"|[^"])*")|(?>\'(?:\\\\\'|[^\'])*\')|\((?:(?R)|[' . $seperator . '])*\)|(?>[^`"\'()' . $seperator . ']+))+)/', $string, $matches, PREG_SET_ORDER);
	
	foreach ($matches as $match) {
	    if (empty($match[1])) {
	        $values[] = $unquote ? unquote(trim($match[2]), $unquote) : trim($match[2]); 
	    } else {
            parse_key(trim($match[1]), $unquote ? unquote(trim($match[2]), $unquote) : trim($match[2]), $values);
	    }
	}
	
	return $values;
}

/**
 * Extract the parameters from a DSN string.
 * The first item of the returned array is 'driver'=>driver, other items are arguments/properties.
 * 
 * @param string $dsn
 * @return array
 */
function extract_dsn($dsn)
{
	$matches = null;
	if (!preg_match('/^([\w-]+)\:(.*)$/', $dsn, $matches)) return array('driver'=>$dsn);
	return array('driver'=>$matches[1]) + split_set_assoc($matches[2], ';');
}


// -------- Integer -------

/**
 * Split all items in a binairy set
 * 
 * @param int $value
 * @return array
 */
function split_binset($value)
{
	$array = array();
	$count = 0;
	
	while ($value) {
		if (($value & 1)) $array[] = pow(2, $count);
		$value = $value>>1;
		$count++;
	}
	return $array;
}


// -------- Array -------

/**
 * Create a binary set, by doing each key to the power of 2.
 *
 * @param array $array  An array with numeric keys
 * @return array
 */
function binset(array $array)
{
    $result = array();
    foreach ($array as $i=>$item) $result[pow(2, $i)] = $item;
    return $result;
}

/**
 * Get a column in a 2 dimensional array.
 * 
 * @param array $array
 * @param mixed $key
 * @return array
 */
function array_get_column($array, $key)
{
	$result = array();
	foreach ($array as $i=>$item) {
	    $result[$i] = isset($item[$key]) ? $item[$key] : null;
	}
	return $result;
}

/**
 * Filter an array, only using items with the specified keys.
 * Returns the result in order of $keys.
 * 
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_filter_keys($array, $keys)
{
    $result = array();
    foreach ($keys as $key) {
        if (array_key_exists($key, $array)) $result[$key] = $array[$key]; 
    }
    return $result;
}

/**
 * Merges the elements of one or more arrays together so that the values of one are appended to the end of the previous one. It returns the resulting array.
 *
 * If the input arrays have the same string keys, then the later value for that key will overwrite the previous one, but only if one of the values isn't an array.
 * If both values are arrays, the values will be merged recursively.
 * If, however, the arrays contain numeric keys, the later value will not overwrite the original value, but will be appended.
 * This function is slightly different from array_replace_recursive, which also overwrites numeric keys.  
 *
 * @param array $array1
 * @param array $array2
 * @param Additional arrays may be given.
 * @return array
 * 
 * @todo Remove this, replacing it with the PHP equivelant.
 * @deprecated 
 */
function array_merge_recursive($array1, $array2)
{
    unset($array1, $array2);
    $arrays = func_get_args();
    
    $array = array_shift($arrays);
    if (!is_array($array)) {
        trigger_error("Argument #1 of " . __FUNCTION__ . " isn't an array.", E_USER_WARNING);
        $array = array();
    }
    
    foreach ($arrays as $i=>$merge) {
        if (!is_array($array)) {
            trigger_error("Argument #$i of " . __FUNCTION__ . " isn't an array.", E_USER_WARNING);
            $array = array();
        }
        
        foreach ($merge as $key=>$value) {
            if (is_int($key)) {
                $array[] = $value;  
            } elseif (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
                $array[$key] = array_merge_recursive($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        }
    }
    
    return $array;
}

/**
 * Turn a one dimensional array into a multidimensional array. 
 *
 * @param array  $array
 * @param string $key        Only for this key
 * @param string $separator
 * @return array
 */
function array_chunk_assoc(array $array, $key=null, $separator='.')
{
	foreach ($array as $k=>$value) {
		if (isset($key) ? !preg_match('/^' . preg_quote($key . $separator, '/') . '/i', $k) : strpos($k, $separator) === false) continue;

		$ci =& $array;
		foreach (explode($separator, $k) as $kx) $ci =& $ci[$kx];
		$ci = $value;
	}
	
	return !isset($key) ? $array : (isset($array[$key]) ? $array[$key] : null);
}

/**
 * Turn a multidimensional array into a one dimensional array. 
 *
 * @param array  $array
 * @param string $key    Prefix
 * @param string $glue
 * @return array
 */
function array_combine_assoc(array $array, $key=null, $glue='.')
{
    $result = array();
    
    foreach ($array as $k=>$value) {
        if (is_array($value)) $result += array_combine_assoc($value, (isset($key) ? $key . $glue . $k : $k), $glue);
          else $result[(isset($key) ? $key . $glue . $k : $k)] = $value;
    }
    
    return $result;
}

/**
 * Sort an associated array, based on if the key is found using in another part in the array.
 * 
 * The values of each part are dependencies on another part, like in the syntax in a MAKE file. The first item has no dependencies,
 *  the second item can only have dependencies on the first item, etc.
 * 
 * @example refsort('p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2'), 'ch1'=>null, 'ch3'=>null));
 * Result: array('ch3'=>null, 'ch1'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2'))
 * Parts 'c1' and 'c2' are placed before 'p2' because there are found in the array of 'p2'. Part 'p2' is 
 *  placed before 'p1', because 'p2' is used in 'p1'.
 * 
 * @param array $array       array(key=>array(), ...)
 * @param int   $sort_flags  Allowed flags: SORT_ASC, SORT_DESC
 * @return array
 */
function refsort($array, $sort_flags=SORT_ASC)
{
	$max = 0;
	$i = 0;
	$keys = array_keys($array);
	
	while ($i<sizeof($array)) {
		$found_first = null;
		$found_last = null;
		for ($j=$i+1; $j<sizeof($array); $j++) {
			if (isset($array[$keys[$j]]) && in_array($keys[$i], $array[$keys[$j]])) {
				if (!isset($found_first)) $found_first = $j;
				$found_last = $j;
			}
		}
		
		if (isset($found_first)) {
			$cut = array_splice($array, $i, $found_first-$i);
			$after = array_splice($array, $found_last-sizeof($cut)+1);
			$array += $cut + $after;
			$keys = array_keys($array);
		} else {
			$max = 0;
			$i++;
		}
		
		if ($max++ > sizeof($array)) {
			trigger_error("Unable to sort array because of cross-reference.", E_USER_WARNING);
			return $array;
		}
	}
	
	return $sort_flags === SORT_DESC ? $array : array_reverse($array, true);
}

/**
 * Join array elements with a glue string recursively. 
 *
 * @param string $glue   
 * @param array  $array
 * @param string $group_prefix
 * @param string $group_suffix
 * @return string
 */
function implode_recursive($glue, $array, $group_prefix='(', $group_suffix=')')
{
    if (empty($array)) return '';
    
    $result = "";
    foreach ($array as $item) {
        $result .= (isset($item) && is_scalar($item) ? $item : $group_prefix . implode_recursive($glue, $item, $group_prefix, $group_suffix) . $group_suffix) . $glue;
    }
    
    return substr($result, 0, -1 * strlen($glue));
}

/**
 * Join array elements as key=value with a glue string.
 *
 * @param string $glue
 * @param array  $array
 * @param string $format        Format for each pair in sprintf format: key, value, full key. Can be array(format num, format assoc).
 * @param string $group_prefix  Use %s for key
 * @param string $group_suffix
 * @param string $quote         Quote values (default is where needed)
 * @param string $key_prefix
 * @return string
 */
function implode_assoc($glue, $array, $format=array('%2$s', '%3$s=%2$s'), $group_prefix='', $group_suffix='', $quote=null, $key_prefix=null)
{
    if (empty($array)) return '';
    
    if (is_array($format)) list($format_num, $format_assoc) = $format;
      else $format_num = $format_assoc = $format;
    
    $result = "";
    foreach ($array as $key=>$value) {
        if (is_array($value)) {
            $result .= sprintf($group_prefix, $key) . implode_assoc($glue, $value, $format_assoc, $group_prefix, $group_suffix, $quote, $key_prefix . $key . '.') . $group_suffix  . $glue;
        } else {
            if ($quote || (!isset($quote) && (strpos($value, $glue) !== false || strpos($value, '=') !== false))) $value = '"' . addcslashes($value, '"') . '"';
            $result .= sprintf(is_int($key) ? $format_num : $format_assoc, $key, $value, $key_prefix . $key) . $glue;
        }
    }
    
    return substr($result, 0, -1 * strlen($glue));
}

/**
 * Returns an array containing all the elements of array after applying the callback function to each one, decending into subarray. 
 *
 * @param callback $callback
 * @param array    $array
 * @return array
 */
function array_map_recursive($callback, array $array)
{
    foreach ($array as &$value) {
        $value = is_array($value) ? array_map_recursive($callback, $value) : call_user_func($callback, $value);
    }
    return $array;
}

// -------- PHP --------

/**
 * Same as var_export, except that an object will not be serialized but cast to a string instead.
 * 
 * @param mixed   $expression
 * @param boolean $return      Return value instead
 * @param boolean $objects     Set to false to skip object with a warning
 * @return string
 */
function var_give($expression, $return=false, $objects=true)
{
    if (is_object($expression) && get_class($expression) != 'stdClass') {
        if (!$objects) {
            trigger_error("Won't serialize an object: Trying to serialize " . get_class($expression) . '.', E_USER_WARNING);
            return 'null'; 
        }
        $var = '(' . get_class($expression) . ') ' . (method_exists($expression, '__toString') ? ' ' . (string)$expression : spl_object_hash($expression));
    } elseif (is_array($expression) || is_object($expression)) {
        $args = array();
        foreach ($expression as $k=>$v) $args[] = ' ' . (is_string($k) ? "'$k'" : $k) . ' => ' . var_give($v, true);     
        $var = 'array (' .  join(',', $args) . ' )';
        if (is_object($expression)) $var = "stdClass::__set_state($var)";
    } elseif (is_string($expression)) {
        $var = "'" . addcslashes($expression, "'") . "'";
    } else {
        return var_export($expression, $return);
    }
    
    if ($return) return $var;
    
    echo $var;
    return null;
}

/**
 * Serialize a debug_backtrace
 *
 * @param array      $trace
 * @param int|arrray $unset_first  Unset the first x number of entry's or unset until using array('file'=>file, 'line'=>line)
 */	
function serialize_trace($trace, $unset_first=0)
{
	if (is_array($unset_first)) {
	    do {
            $step = array_shift($trace);
        } while ($step && (!isset($step['file']) || !isset($step['line']) || $step['file'] != $unset_first['file'] || $step['line'] != $unset_first['line']));
	} else {
        while ($unset_first--) array_shift($trace);
	}
	
	$messages = array();
	foreach ($trace as $count=>$step) {
	    if (!isset($step['file'])) $step['file'] = 'unknown';
	    if (!isset($step['line'])) $step['line'] = 'unknown';
	    
        $args = array();
        if (isset($step['args'])) {
            foreach ($step['args'] as $arg) $args[] = str_replace("\n", chr(182), var_give($arg, true));
        }
	    
		$messages[$count] = "#$count {$step['file']} ({$step['line']}): " . (isset($step['class']) ? $step['class'] . $step['type'] : '') . "{$step['function']}(" . join(', ', $args) . ")";
	}
	return join("\n", $messages);
}
?>