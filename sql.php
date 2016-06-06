<?php
	/***********************************
	*             SETTINGS             *
	***********************************/
	/**/ $username = "mortech";     /**/
	/**/ $password = "webdroppass"; /**/
	/**/ $database = "webdrop";     /**/
	/**********************************/
	/**********************************/


	// Turn off all error reporting
	error_reporting(0);
	$conn = new mysqli("127.0.0.1",$username,$password);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error . "<br>Make sure your cridentials in sql.php are correct");
	}
	
	//Try to connect to the database
	$result = $conn->select_db($database);
	if (!$result) {
		echo "Database does not exist, or we do not have permission to use it.<br>";
		
		//Try to create the database
		echo "Trying to create database: $database<br>";
		$result = $conn->query("CREATE DATABASE $database");
		if (!$result) {
			die("We can't create the table.  Please configure mysql properly for this user<br>" . $conn->error);
		}
		$conn->select_db($database);
		echo "Successfully created database!  Running setup...<br>";
		
		
		//CREATE FILES TABLE
		echo "Creating table: files<br>";
		$result = $conn->query("CREATE TABLE files ( id INT NOT NULL AUTO_INCREMENT , utc INT(11) NOT NULL, uid INT(11) NOT NULL, token VARCHAR(20) NOT NULL , password VARCHAR(20) NOT NULL , path VARCHAR(255) NOT NULL , expires INT(11) NOT NULL, PRIMARY KEY (id))");
		if (!$result) {
			echo $conn->error . "<br>";
		}
		
		//CREATE USERS TABLE
		echo "Creating table: users<br>";
		$result = $conn->query("CREATE TABLE user ( id INT(11) NOT NULL AUTO_INCREMENT , username VARCHAR(255) NOT NULL UNIQUE, email VARCHAR(255) NOT NULL, fullname VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, admin INT(1) DEFAULT 0 , PRIMARY KEY (id))");
		if (!$result) {
			echo $conn->error . "<br>";
		}
		
		//CREATE LOG
		echo "Creating table: log<br>";
		$result = $conn->query("CREATE TABLE log ( id INT(11) NOT NULL AUTO_INCREMENT , utc INT(11) NOT NULL, fid INT(11) NOT NULL, ip VARCHAR(255), PRIMARY KEY (id))");
		if (!$result) {
			echo $conn->error . "<br>";
		}
		
		//Create default user
		echo "Creating default admin account (user: admin, pass: admin)<br>";
		$pass = crypt("admin");
		$result = $conn->query("INSERT INTO user(username,password,email,fullname,admin) VALUES('admin','$pass','admin@example.com','Webdrop Admin',1)");
		if (!$result) {
			echo $conn->error . "<br>";
		}
	}
	
	// Turn on all error reporting
	error_reporting(-1);
 
?>