<?php
namespace Q;

/**
 * Use a global variable as stream.
 *
 * @package Misc
 */
class VariableStream
{
    protected $position;
    protected $varname;

    /**
     * Callback for fopen().
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     * @return boolean
     */
	public function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        $this->varname = $url["host"];
        $this->position = 0;

        return true;
    }

    /**
     * Callback for fread().
     *
     * @param int $count
     * @return string
     */
    public function stream_read($count)
    {
		if (!isset($GLOBALS[$this->varname])) return '';
    	
        $ret = substr($GLOBALS[$this->varname], $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    /**
     * Callback for fwrite().
     *
     * @param string $data
     * @return int
     */
    function stream_write($data)
    {
    	if (!isset($GLOBALS[$this->varname])) {
    		$GLOBALS[$this->varname] = $data;
    	} else {
        	$GLOBALS[$this->varname] = substr_replace($GLOBALS[$this->varname], $data, $this->position, strlen($data));
    	}

    	$this->position += strlen($data);
        return strlen($data);
    }

    /**
     * Callback for ftell().
     *
     * @return int
     */
    function stream_tell()
    {
    	return $this->position;
    }

    /**
     * Callback for feof().
     *
     * @return int
     */
    function stream_eof()
    {
        return !isset($GLOBALS[$this->varname]) || $this->position >= strlen($GLOBALS[$this->varname]);
    }

    /**
     * Callback for fseek().
     *
     * @return boolean
     */
    function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen($GLOBALS[$this->varname]) && $offset >= 0) {
                     $this->position = $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_CUR:
                if ($offset >= 0) {
                     $this->position += $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_END:
                if (strlen($GLOBALS[$this->varname]) + $offset >= 0) {
                     $this->position = strlen($GLOBALS[$this->varname]) + $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            default:
            	trigger_error("Unknown whence for fseek of global variable \${$this->varname}", E_USER_NOTICE);
                return false;
        }
    }
    
    /**
     * Callback for fstat().
     *
     * @return array
     */
    public function stream_stat()
    {
    	return array();
    }
}

stream_register_wrapper('global', 'Q\VariableStream');

?>