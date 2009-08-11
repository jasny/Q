<?php
namespace Q;

/**
 * Yet another static class with console functions.
 * 
 * @package Console
 */
class Console
{
    /**
     * Console text color chars
     * @var array
     */
    static protected $color_text = array(
      'black'  => 30,
      'red'    => 31,
      'green'  => 32,
      'brown'  => 33,
      'blue'   => 34,
      'purple' => 35,
      'cyan'   => 36,
      'grey'   => 37,
      'yellow' => 33
    );
    
    /**
     * Console text style chars
     * @var array
     */
    static protected $color_style = array(
      'normal'     => 0,
      'bold'       => 1,
      'light'      => 1,
      'underscore' => 4,
      'underline'  => 4,
      'blink'      => 5,
      'inverse'    => 6,
      'hidden'     => 8,
      'concealed'  => 8
    );
    
    /**
     * Console background color chars
     * @var array
     */
    static protected $color_background = array(
      'black'  => 40,
      'red'    => 41,
      'green'  => 42,
      'brown'  => 43,
      'yellow' => 43,
      'blue'   => 44,
      'purple' => 45,
      'cyan'   => 46,
      'grey'   => 47
    );
    
    
    /**
     * Map for short options.
     * 
     * As array(shortopt=>longopt, ...).
     * Add an '=' at the end of the long option to indicate the next arg is the value of the opt.
     * 
     * @var array
     */
    static protected $optmap;
        
    /**
     * Parsed options.
     * @var array
     */
    static protected $opts;
    
    /**
     * Command-line arguments that are not options
     * @var array
     */
    static protected $args;
    
    
    /**
     * Set map for short options.
     * Add an '=' at the end of the long option to indicate the next argument is the value of the option.
     * 
     * {@example Console::setOptMap(array('h'=>'help', 'q'=>'quiet', 'f'=>'file=');}}
     * 
     * @param array $map  array(shortopt=>longopt, ...)
     */
    static public function setOptMap($map)
    {
        self::$opts = null;
        self::$args = null;

        self::$optmap = is_string($map) ? split_set(';', $map) : $map;
    }
    
    /**
     * Parse command line options
     */
    static protected function parseOptions()
    {
        self::$opts = array();
        self::$args = array();
        
        if (empty($_SERVER['argv'])) return;
        
        for ($i=0, $n=count($_SERVER['argv']); $i<$n; $i++) {
            $arg = $_SERVER['argv'][$i];
            
            if ($arg[0] != '-') {
                self::$args[] = $arg;
                
            } elseif (strncmp($arg, '--', 2)) {
                list($key, $value) = explode('=', substr($arg, 2), 2) + array(1=>true);
                
                if (!isset(self::$opts[$key])) {
                    self::$opts[$key] = $value;
                } else {
                    self::$opts[$key] = (array)self::$opts[$key];
                    self::$opts[$key][] = $value;
                }
                
            } else {
                for ($j=1, $m=strlen($arg); $j<$n; $j++) {
                    $key = $arg[$j];
                    $value = true;
                    
                    if (isset(self::$optmap[$key])) {
                        $key = self::$optmap[$key];
                        if (substr($key, -1) == '=') {
                            $key = substr($key, 0, -1);
                            $value = $_SERVER['argv'][++$i];
                        }
                    }
                    
                    if (!isset(self::$opts[$key])) {
                        self::$opts[$key] = $value;
                    } else {
                        self::$opts[$key] = (array)self::$opts[$key];
                        self::$opts[$key][] = $value;
                    }
                }
            }
        }
    }
    
    /**
     * Get command-line option.
     * 
     * @param string $key
     * @return string|true
     */
    static public function getOpt($key)
    {
        if (!isset(self::$opts)) self::parseOptions();
        return isset(self::$opts[$key]) ? self::$opts[$key] : null;
    }
    
    /**
     * Get all command-line options.
     * 
     * @return array
     */
    static public function getOpts()
    {
        if (!isset(self::$opts[$key])) self::parseOptions();
        return self::$opts;
    }
    
    /**
     * Get command-line argument.
     * 
     * @param int $pos
     * @return string
     */
    static public function getArg($pos)
    {
        if (!isset(self::$args)) self::parseOptions();
        return isset(self::$args[$pos]) ? self::$args[$pos] : null;
    }
    
    /**
     * Get all command-line arguments.
     * 
     * @return array
     */
    static public function getArgs()
    {
        if (!isset(self::$args)) self::parseOptions();
        return self::$args;
    }
    

    /**
     * Return the text with console color and style.
     * 
     * @param string $text
     * @param string $color    Text color name
     * @param string $style    Text style name
     * @param string $bgcolor  Background color name
     * @return string
     */
    static public function text($text, $color=null, $style=null, $bgcolor=null)
    {
        return (isset($color) ? "\033[" . self::$color_text[$color] . 'm' : '')
         . (isset($style) ? "\033[" . self::$color_style[$style] . 'm' : '')
         . (isset($bgcolor) ? "\033[" . self::$color_background[$bgcolor] . 'm' : '')
         . $text . "\033[0m";
    }
    
    /**
     * Get a character for a text color on the console.
     * 
     * @param string 
     * @return string
     */
    static public function textColor($name)
    {
        return "\033[" . self::$color_text[$name] . 'm';
    }
    
    /**
     * Get a character for a text style on the console.
     * 
     * @return string
     */
    static public function textStyle($name)
    {
        return "\033[" . self::$color_style[$name] . 'm';
    }
    
    /**
     * Get a character for a background color on the console.
     * 
     * @return string
     */
    static public function backgroundColor($name)
    {
        return "\033[" . self::$color_background[$name] . 'm';
    }

    /**
     * Get the character to reset the console color and style.
     * 
     * @return string
     */
    static public function resetColor()
    {
        return "\033[0m";
    }
}
