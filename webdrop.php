<?php
	include "util.php";
	include "sql.php";
	
	//Get the id of the file that should be downloaded
	$token = get("token");
	
	if (!$token) {
		//No file
		echo "Invalid link<br>";
		exit();
	}
	
	$password = post("password");
	if (!$password) {
		echo "Please enter the unique password to download this file<br>";
		echo "<form method='post' action=''>
					<input type='password' name='password'>
					<input type='submit' value='Submit'>
				</form>";
		exit();
	}
	
	$filepath = "";	//Where the file path will be stored
	
	//Check if this password matches the database password
	$stmt = $conn->prepare("SELECT path FROM files WHERE token=? AND password=? LIMIT 1");
	$stmt->bind_param("ss",$token,$password);
	$stmt->bind_result($filepath);
	$stmt->execute();
	
	if (!$stmt->fetch()) {
		echo "Invalid file or password";
		exit();
	}
	
	//Append the path prefix
	$filepath = "\\\\nas5\\webdrop\\" . $filepath;
	
	$stmt->close();
	
	$file = fopen($filepath,"rb");
	$quoted = sprintf('"%s"', addcslashes(basename($filepath), '"\\'));
	$size   = filesize($filepath);
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . $quoted); 
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . $size);
	
	$buffersize = 1024 * 1024 * 1;	//1MB at a time
	while (!feof($file)) {
		echo fread($file,$buffersize);
	}
	
	fclose($file);
?>