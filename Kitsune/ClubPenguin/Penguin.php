<?php

namespace Kitsune\ClubPenguin;

class Penguin {

	public $socket;
	
	public function __construct($socket) {
		$this->socket = $socket;
	}
	
	public function send($data) {
		$data .= "\0";
		$bytes_written = socket_send($this->socket, $data, strlen($data), 0);
		
		return $bytes_written;
	}
	
}

?>