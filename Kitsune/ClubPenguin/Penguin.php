<?php

namespace Kitsune\ClubPenguin;
use Kitsune;

class Penguin {

	public $id;
	public $username;
	public $swid;

	public $random_key;
	public $socket;
	public $database;
	
	public function __construct($socket) {
		$this->socket = $socket;
		$this->database = new Kitsune\Database();
	}
	
	public function send($data) {
		echo "Outgoing: $data\n";
		$data .= "\0";
		$bytes_written = socket_send($this->socket, $data, strlen($data), 0);
		
		return $bytes_written;
	}
	
}

?>