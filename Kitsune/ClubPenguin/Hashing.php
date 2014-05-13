<?php

namespace Kitsune\ClubPenguin;

final class Hashing {

	private static $character_set = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789`~!@#$%^&*()_+-={}|[]\:\"',./?";
	
	public static function generateRandomKey() {
		$key_length = mt_rand(7, 10);
		$random_key = "";
		
		foreach(range(0, $key_length) as $current_length) {
			$random_key .= substr(self::$character_set, mt_rand(0, strlen(self::$character_set)), 1);
		}
		
		return $random_key;
	}
	
	public static function encryptPassword($password, $md5 = true) {
		if($md5 !== false) {
			$password = md5($password);
		}
		
		$hash = substr($password, 16, 16) . substr($password, 0, 16);
		return $hash;
	}

	public static function getLoginHash($password, $random_key) {		
		$hash = self::encryptPassword($password, false);
		$hash .= $random_key;
		$hash .= "a1ebe00441f5aecb185d0ec178ca2305Y(02.>'H}t\":E1_root";
		$hash = self::encryptPassword($hash);
		
		return $hash;
	}
	
}

?>