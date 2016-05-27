<?php
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
?>