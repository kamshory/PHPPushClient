# PHPPushClient

PHP is a programming language that is more widely used to build web applications. However, PHP can also be used to create backend applications that run a process behind the scenes. This process is run by a PHP engine and can be linked to a database.

One application that requires a backend process is a payment application that is connected to a virtual account. The application can receive payment confirmation from banks or third parties without having to create a server. The application still acts as a client.

PHP will make a connection to the virtual account server as a client. When a payment occurs, the server sends a payment confirmation and the client can update the payment status.

This program must be run with a console and cannot be accessed with a browser. This program will run in the background continuously. If the connection is lost, the program will try to establish a new connection.

Process indicator to indicate that the process is running. Task scheduller can check the port opened by this program. If process is running, it will send "OK".

# Requirement

This library need Thread class. In case it not installed on your server, you can download it from
https://windows.php.net/downloads/pecl/releases/pthreads/2.0.10/

See https://secure.php.net/manual/en/pthreads.installation.php to install

# Usage

```php
require "lib/process.php";
require "lib/phppushclient.php";

class AltoVAUsage
{
	public $username = "";
	public $code = "";
	public $password = "";
	public $serverAddress = "localhost";
	public $serverPort = 9001;
	public $sslPushClient = null;
	public $waitReconnect = 5000000; // Micro second
	public $ssl = false;
	public function __construct($serverAddress, $serverPort, $ssl, $username, $code, $password)
	{
		$this->serverAddress = $serverAddress;
		$this->serverPort = $serverPort;
		$this->ssl = $ssl;
		$this->username = $username;
		$this->code = $code;
		$this->password = $password;
	}
	public function init()
	{
		$this->sslPushClient = new SSLPushClient($this->serverAddress, $this->serverPort, $this->ssl);
		
		// TODO Define you own function here...
		$this->sslPushClient->onMessageReceived = function($header, $body)
		{
			// TODO Add your code here to receive the message
			$jsonObject = json_decode($body, TRUE);
			$type = trim($jsonObject['type']);
			$data = $jsonObject['data'];
			if($type == 'PAYMENT-CONFIRMATION')
			{
				$bill_id = $data["bill_id"];
				$bill_number = $data["bill_number"];
				$bill_number_internal = $data["bill_number_internal"];
				$customer_code = $data["customer_code"];
				$payment_date_time = $data["payment_date_time"];
				$amount = $data["amount"];
				$channel_type_id = $data["channel_type_id"];
				$currency_id = $data["currency_id"];
				$description_1 = $data["description_1"];
				$description_2 = $data["description_2"];
				$bill_subcompany = $data["bill_subcompany"];
				$stan = $data["stan"];
				
				// Add your filter here
				$bill_id = addslashes($bill_id);
				$bill_number = addslashes($bill_number);
				$bill_number_internal = addslashes($bill_number_internal);
				$customer_code = addslashes($customer_code);
				$payment_date_time = addslashes($payment_date_time);
				$amount = addslashes($amount);
				$channel_type_id = addslashes($channel_type_id);
				$currency_id = addslashes($currency_id);
				$description_1 = addslashes($description_1);
				$description_2 = addslashes($description_2);
				$bill_subcompany = addslashes($bill_subcompany);
				$stan = addslashes($stan);

				$sqlUpdate = "update bill set paid = 1, payment_date_time = '".$payment_date_time."', channel_type_id = '".$channel_type_id."', payment_amount = ".$amount." ".
				"where bill_number = '".$bill_number."' and stan = '".$stan."' ";
				// Execute this SQL on your database

			}
			
			
		};
		$this->sslPushClient->onConnected = function()
		{
			// TODO Add you code here when connected
			echo "CONNECTED... \r\n";
		};
		$this->sslPushClient->onDisconnected = function()
		{
			// TODO Add you code here when disconnected
			echo "DISCONNECTED... \r\n";
			$this->connected = false;
			$this->connect();
			$this->start();
		};
		return $this;
	}
	public function connect()
	{
		$this->sslPushClient->connect();
		if($this->sslPushClient->connected)
		{
			$this->connected = true;
		}
		else
		{
			$this->connected = false;
			usleep($this->waitReconnect);
			$this->connect();
		}
		return $this;
	}
	public function login($user = NULL, $code = NULL, $password = NULL)
	{
		$this->sslPushClient->login($user, $code, $password);
		return $this;
	}
	public function start()
	{
		$this->sslPushClient->start();
		return $this;
	}
}

$indicator = new ProcessIndicator("localhost", 9000);
$indicator->start();
$api = new AltoVAUsage("localhost", 9001, true, "user", "code", "password");
$api->init()->connect()->login()->start();

```
