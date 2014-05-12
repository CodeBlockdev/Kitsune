<?php

namespace Kitsune\ClubPenguin;

spl_autoload_register(function ($path) {
	require_once $path . ".php";
});

$cp = new World();
$cp->listen("127.0.0.1", 9875);
while(true) {
	$cp->acceptClients();
}

?>