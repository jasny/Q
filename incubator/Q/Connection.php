<?php
namespace Q;

require_once 'Q/StreamingConnection.php';

/**
 * Wrapper for streams.
 * 
 * @package Connection
 */
class Connection implements StreamingConnection
{
	/**
	 * Connection string
	 * @var string
	 */
	protected $url;

	/**
	 * Connection context
	 * @var resource
	 */
	protected $context;
		
	/**
	 * Input/ouput stream
	 * @var resource
	 */
	protected $resource;

	/**
	 * Extra info
	 * @var array
	 */
	protected $extra=null;
	

	/**
	 * Class constructor
	 *
	 * @param resource|string $stream  Stream or URL 
	 * @param array           $options
	 */
	public function __construct($stream, $options)
	{
		if (!is_resource($stream)) {
			$this->url = $stream;

			$context = array_chunk_assoc($options, 'context');
			if (!empty($context)) $this->context = stream_context_create($context);

			$stream = fopen($stream, 'rw', null, $this->context);
		} else {
			$meta = stream_get_meta_data($stream);
			$this->url = $meta['url'];
		}
		
		$this->resource = $stream;
	}
	
	/**
	 * Check if the connection is open.
	 *
 	 * @todo Find a better way to find out if a connection is closed.
	 */
	public function isOpen()
	{
		return isset($this->resource);
	}
	
	/**
	 * Reopen a closed connection.
	 */
	public function reconnect()
	{
		if (!isset($this->url)) throw new Exception("Unable to reconnect: Connection URI unknown.");
		
		if (isset($this->resource)) fclose($this->resource);
		$this->resource = fopen($this->url, 'rw', null, $this->context);
	}
	
	/**
	 * Close the connection.
	 */
	public function close()
	{
		$meta_data = stream_get_meta_data($this->resource);
		$this->extra = $meta_data['wrapper_data'];

		stream_socket_shutdown($this->resource, STREAM_SHUT_RDWR);	// Don't allow any more reads/writes
		stream_get_contents($this->resource);						// Clear any stuff on the socket, otherwise it will stay open
		fclose($this->resource);
		$this->resource = null;
	}
	
	/**
	 * Get the stream.
	 * 
	 * @return resource
	 */
	public function forInput()
	{
		return $this->resource;
	}
	
	/**
	 * Get the stream.
	 * 
	 * @return resource
	 */
	public function forOutput()
	{
		return $this->resource;
	}
	
	/**
	 * Get extra info from meta data
	 * 
	 * @return string
	 */
	public function getExtraInfo()
	{
		if (!isset($this->resource)) return $this->extra;
		
		$meta_data = stream_get_meta_data($this->resource);
		return $meta_data['wrapper_data'];
	}
	
	/**
	 * Return information about the connection.
	 *
	 * @return string
	 */
	public function about()
	{
		if (empty($this->url)) return "Unnamed connection";
		return preg_replace('/\:\w*@/', '@', $this->url);
	}
}

?>