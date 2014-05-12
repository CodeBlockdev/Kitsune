<?php

namespace Kitsune\ClubPenguin;

spl_autoload_register(function ($path) {
	require_once $path . ".php";
});

$cp = new Login();
$cp->listen("127.0.0.1", 6112);
while(true) {
	$cp->acceptClients();
}

?>