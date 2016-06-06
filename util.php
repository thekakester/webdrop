<?php
	session_start();
	
	function get($val) {
		if (isset($_GET[$val])) {
			return $_GET[$val];
		}
		return false;
	}
	
	function post($val) {
		if (isset($_POST[$val])) {
			return $_POST[$val];
		}
		return false;
	}
	
	function session($val) {
		if (isset($_SESSION[$val])) {
			return $_SESSION[$val];
		}
		return false;
	}
?>