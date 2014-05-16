<?php

namespace Kitsune;

abstract class Spirit {

	protected $sockets = array();
	protected $port;
	protected $master_socket;

	private function accept() {
		$client_socket = socket_accept($this->master_socket);
		socket_set_option($client_socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_nonblock($client_socket);
		$this->sockets[] = $client_socket;
		
		return $client_socket;
	}

	protected function handleAccept($socket) {
		echo "Client accepted\n";
	}

	protected function handleDisconnect($socket) {
		echo "Client disconnected\n";
	}

	protected function handleReceive($socket, $data) {
		echo "Received data: $data\n";
	}

	protected function removeClient($socket) {
		$client = array_search($socket, $this->sockets);
		unset($this->sockets[$client]);
		socket_close($socket);
	}

	public function listen($address, $port, $backlog = 5) {
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_nonblock($socket);
		socket_bind($socket, $address, $port);
		socket_listen($socket, $backlog);

		$this->master_socket = $socket;
		$this->port = $port;
	}

	public function acceptClients() {
		$sockets = array_merge(array($this->master_socket), $this->sockets);
		$tv_usec = mt_rand(20, 70);
		$changed_sockets = socket_select($sockets, $write, $except, $tv_usec);
		if($changed_sockets === 0) {
			return false;
		} else {
			if(in_array($this->master_socket, $sockets)) {
				$client_socket = $this->accept();
				$this->handleAccept($client_socket);
				unset($sockets[0]);
			}
			
			foreach($sockets as $socket) {
				$mixed_status = socket_recv($socket, $buffer, 8192, 0);
				if($mixed_status == null) {
					$this->handleDisconnect($socket);
					$this->removeClient($socket);
					continue;
				} else {
					$this->handleReceive($socket, $buffer);
				}
			}
		}
	}

}

ob_implicit_flush();

?>	