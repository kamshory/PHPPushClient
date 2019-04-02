class ProcessIndicator extends Thread 
{
	public $host = "localhost";
	public $port = 80;
	public $serverSocket = NULL;
	public function __construct($host, $port)
	{
		$this->host = $host;
		$this->port = $port;
	}
	public function run()
	{
		$this->createServer();
	}
	public function createServer()
	{
		set_time_limit(0);
		$this->serverSocket = socket_create(AF_INET, SOCK_STREAM, 0);
		$result = socket_bind($this->serverSocket, $this->host, $this->port);
		$result = socket_listen($this->serverSocket, 3);
		while(TRUE)
		{
			$client = new RequestHandler(socket_accept($this->serverSocket));
			$client->start();
		}
	}
}
class RequestHandler extends Thread 
{
	public $socket = NULL;
	public function __construct($socket)
	{
		$this->socket = $socket;
	}
	public function run()
	{
		$output = "OK";
		socket_write($this->socket, $output, strlen ($output));
		socket_shutdown($this->socket, 2);
		socket_close($this->socket);
	}
}
