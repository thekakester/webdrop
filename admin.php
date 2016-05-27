<?php
	include "util.php";
	include "sql.php";

	
	//If they're trying to generate a link
	$publish = get("publish");
	if ($publish) {
		$tokenSize = 15;
	
		$foundToken = false;
		$loopCounter = 0;
		while (!$foundToken && $loopCounter < 1000) {
			$loopCounter++;	//Avoid infinite loops
			
			$token = randomhash($tokenSize);
			//echo "Trying token $token<br>";
			$result = $conn->query("SELECT count(*) FROM files WHERE token='$token'");
			if ($row = $result->fetch_array()) {
				if ($row[0] == 0) {
					$foundToken = true;
				}
			}
		}
		
		if (!$foundToken) {
			echo "SEVERE ERROR: Cannot generate a new link.  All tokens are in use<br>";
			exit();
		}
		
		//Generate a password
		$password = randomhash(8);
		
		$stmt = $conn->prepare("INSERT INTO files(token,password,path) VALUES(?,?,?)");
		$stmt->bind_param("sss",$token,$password,$publish);
		$stmt->execute();
		$stmt->close();
		
	}
	
	$delete = get("delete");
	if ($delete) {
		$stmt = $conn->prepare("DELETE FROM files WHERE id=?");
		$stmt->bind_param("i",$delete);
		$stmt->execute();
	}
	
	
	
	
	echo "THIS PAGE IS ONLY VISIBLE WITHIN THIS BUILDING<br>";

	echo "<table border=1>
	<tr><th>Filename</th><th>Download Link</th><th>Password</th><th>Delete</th></tr>";
	
	//Listing all current links
	$result = $conn->query("SELECT token,path,password,id FROM files");
	while ($row = $result->fetch_assoc()) {
		$link = "http://168.192.1.198/webdrop/webdrop.php?token=$row[token]";
		echo "<tr><td>$row[path]</td><td><a href='$link'>$link</a></td><td>$row[password]</td><td><a href='?delete=$row[id]'>X</a></td></tr>";
	}
	echo "</table>";
	echo "<br><br>";
	
	//List all the files in the directory
	echo "<br><b>Files in NAS5:</b>";
	echo "<table border=1>";
	$files = scandir("\\\\nas5\\webdrop");
	foreach ($files as $f) {
		if ($f == "." || $f=="..") { continue; }
		$url = urlencode($f);
		echo "<tr><td>$f</td><td><a href='?publish=$url'>Generate Link</a></td></tr>";
	}
	echo "</table>";

	function randomhash($len) {
		$hash = "";
		while (strlen($hash) < $len) {
			$nextChar = rand(0,35);
			if ($nextChar >= 10) {
				$nextChar = chr($nextChar - 10 + ord('A'));
			}
			$hash .= $nextChar;
		}
		return $hash;
	}
	
?>