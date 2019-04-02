class SSLPushClient
{
	public $host = "127.0.0.1";
	public $port = 80;
	public $socket = NULL;
	public $clientID = "";
	public $clientPassword = "";
	public $clientCode = "";
	public $ssl = false;
	public $timeout = 10;
	public $connected = false;
	
	/**
	Override this method
	*/	
	public $onMessageReceived = NULL;
	public $onConnected = NULL;
	public $onDisconnected = NULL;

	public function __construct($host, $port, $ssl = false)
	{
		$this->host = $host;
		$this->port = $port;
		$this->ssl = $ssl;
	}
	public function connect()
	{
		if($this->ssl)
		{		
			$context = stream_context_create(
				array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false
				)
			));
	
			$hostname = "ssl://".$this->host.":".$this->port;
			if($this->socket = @stream_socket_client($hostname, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context))
			{
				$this->connected = true;
			}
			else
			{
				$this->connected = false;
			}
		}
		else
		{
			$this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			$result = @socket_connect($this->socket, $this->host, $this->port);
			if($result)
			{
				$this->connected = true;
			}
			else
			{
				$this->connected = false;
			}
		}
		if($this->connected)
		{
			$this->_callConnected();
		}
	}
	public function login($clientID, $clientCode, $clientPassword)
	{
		$raw = array(
			'command'=>'login',
			'data'=>array(
				'client_code'=>$this->clientCode,
				'password'=>$this->clientPassword
			)
		);
		$data = $this->_buildData(json_encode($raw));
		$this->_sendData($data);
	}
	private function _sendData($data)
	{
		if($this->_write($this->socket, $data, strlen($data)) === FALSE)
		{
			if($out === FALSE)
			{
				$this->_callDisconnected();
			}
		}
	}
	private function _buildData($raw)
	{
		$headers = array(
			'Content-type: application/json',
			'Content-length: '.strlen($raw)
		);
		return implode("\r\n", $headers)."\r\n\r\n".$raw;
	}
	private function _getConetntLength($header)
	{
		$lines = explode("\r\n", $header);
		$length = 0;
		foreach($lines as $key=>$line)
		{
			if(stripos($line, "Content-lenght:") !== false)
			{
				$arr = explode(":", $line);
				$str = trim($arr[1]);
				$length = $str * 1;
			}
		}
		return $length;
	}
	private function _read($file, $length)
	{
		$byte = FALSE;
		if($this->ssl)
		{
			$byte = @fread($file, $length);
		}
		else
		{
			$byte = @socket_read($file, $length);
		}
		if($byte === FALSE || $byte === "" || $byte === NULL)
		{
			$this->_callDisconnected();
		}
		return $byte;
	}
	private function _write($file, $data, $length)
	{
		if($this->ssl)
		{
			return @fwrite($file, $data, $length);
		}
		else
		{
			return @socket_write($file, $data, $length);
		}
	}
	private function _callDisconnected()
	{
		if(is_callable($this->onDisconnected))
		{
			call_user_func($this->onDisconnected); 
		}
		$this->connected = false;
	}
	private function _callConnected()
	{
		if(is_callable($this->onConnected))
		{
			call_user_func($this->onConnected); 
		}
	}
	public function start()
	{
		while($this->connected)
		{
			$header = "";
			$out = false;
			do
			{
				$out = $this->_read($this->socket, 1);
				if($out === FALSE || $out === "" || $out === NULL)
				{
					break;
				}
				$header .= $out;
			}
			while(stripos($header, "\r\n\r\n") === false);
			if($out === FALSE)
			{
				$this->_callDisconnected();
			}
			$contentLength = $this->_getConetntLength($header);
			if($contentLength > 0)
			{
				$body = $this->_read($this->socket, $contentLength);
				call_user_func($this->onMessageReceived, $header, $body); 
			}	
		}
		$this->_callDisconnected();
	}
}
