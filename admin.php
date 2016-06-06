<?php
	include "util.php";
	include "sql.php";
	include "jqueryBootstrap.php";

	if (isset($_GET['logout'])) {
		unset($_SESSION['uid']);
	}
	
	$uid = session('uid');
	if (!$uid) {
		include "login.php";
		exit();
	}
	
	//If they're trying to generate a link
	$publish = get("publish");
	$utc = get("utc");
	if ($publish && $utc) {
		//Make sure this isn't a duplicate
		$count = "7";
		$stmt = $conn->prepare("SELECT COUNT(*) FROM files WHERE utc=? AND path=?");
		$stmt->bind_param("is",$utc,$publish);
		$stmt->bind_result($count);
		$stmt->execute();
		$stmt->fetch();
		$stmt->close();
		
		if ($count > 0) {
			"<script>var alreadyExists==true</script>";
		} else {
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
			
			$expires = strtotime("+1 week");
			$stmt = $conn->prepare("INSERT INTO files(token,utc,password,uid,path,expires) VALUES(?,?,?,?,?,?)");
			$stmt->bind_param("sisisi",$token,$utc,$password,$uid,$publish,$expires);
			$stmt->execute();
			$stmt->close();
		}
	}
	
	$delete = get("delete");
	if ($delete) {
		$stmt = $conn->prepare("DELETE FROM files WHERE id=?");
		$stmt->bind_param("i",$delete);
		$stmt->execute();
	}
	
	/***********************
	* CREATE / MODIFY USER *
	***********************/
	if (post('uid')) {
		modifyAccount();
	}
	
	function modifyAccount() {
		global $precontent,$conn;
		$uid		= post('uid');
		$username 	= post('username');
		$fullname 	= post('fullname');
		$email 		= post('email');
		$password	= post('password');
		$admin		= post('admin');
		
		$admin = $admin ? "1" : "0";
		if ($uid == -1) {
			//We're making a new account
			//Make sure ALL fields are filled in
			if (!$fullname || !$username || !$email || !$password) {
				$precontent = "<div class='bg-danger'>Account creation failed: Required fields were missing</div>";
				return;
			}

			error_reporting(0); // Turn off all error reporting
			$password = crypt($password);
			error_reporting(-1);; // Turn on all error reporting
			$stmt = $conn->prepare("INSERT INTO user (username,password,email,fullname,admin) VALUES(?,?,?,?,?)");
			$stmt->bind_param("ssssi",$username,$password,$email,$fullname,$admin);
			$result = $stmt->execute();
			$stmt->close();
			
			if ($result) {
				$precontent = "<div class='bg-success'>User created</div>";
			} else {
				$precontent = "<div class='bg-danger'>Error creating user account.  Possibly a duplicate username</div>";
			}
		} else {
			if ($uid==1) {$admin = "1"; $fullname = "System Admin"; }	//Admin is always admin
			
			//They're modifying an account, start by setting the required stuff
			$stmt = $conn->prepare("UPDATE user SET email=?, fullname=?, admin=? WHERE id=?");
			$stmt->bind_param("ssii",$email,$fullname,$admin,$uid);
			$stmt->execute();
			$stmt->close();
			
			//Update the password maybe
			if ($password) {
				error_reporting(0); // Turn off all error reporting
				$password = crypt($password);
				error_reporting(-1);; // Turn on all error reporting
				$stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
				$stmt->bind_param("si",$password,$uid);
				$stmt->execute();
				$stmt->close();
			}
		}
	}
	
	
	$modifyUser = get("user");
	if ($modifyUser) {
		$uid = $modifyUser;
		//Get details about this user
		$stmt = $conn->prepare("SELECT * FROM user WHERE id=?");
		$stmt->bind_param("i",$uid);
		$stmt->execute();
		
		$result = $stmt->get_result();
		$username = $fullname = $email = $admin = "";
		$title = "Create User Account";
		$message = "Please fill out all fields to create an account.<br>All fields can be changed later except username";
		$submit = "Create";
		$passwordPlaceholder = "Password";
		if ($row=$result->fetch_assoc()) {
			$passwordPlaceholder = "Change Password";
			$username	=$row['username'];
			$fullname	=$row['fullname'];
			$email		=$row['email'];
			$admin 		= $row['admin'] == 1 ? "checked" : "";
			$title 		= "Modify User";
			$submit 	= "Update";
			$message 	= "Modify this user, then click \"Update\" when finished.<br>
		Changing password is optional.  Leave blank to remain unchanged<br>";
		} else {
			//If there's no account, then we'll just make a new one
			$uid = -1;
		}
		
		
		$disableUsername = ($username=="") ? "" : "disabled";
		
		//The following content will be inserted on the page in the main body
		$precontent = "<form action='?' method='post' class='form-inline'>
		<h2>$title</h2>
		$message<br>
		<input type='hidden' name='uid' value='$uid'>
		<input type='text' placeholder='Username' name='username' value='$username' $disableUsername class='form-control'>
		<input type='text' placeholder='Full Name' name='fullname' value='$fullname' class='form-control'>
		<input type='text' placeholder='Email' name='email' value='$email' class='form-control'>
		<input type='password' placeholder='$passwordPlaceholder' name='password' value='' class='form-control'>
		<input type='submit' value='$submit' class='form-control btn btn-success'>
		<a href='?' class='btn btn-default form-control'>Cancel</a>
		<br>Admin: <input type='checkbox' name='admin' $admin class='form-group'>
		<br><br><hr>
		";
		
		$stmt->close();
	}
	
?>

<body style='background-color: #ddd'>
	<div class='container' style='background-color: #fff'>
	<div style='float: left; width: 100%;'>
		<img src='logo.png' style='float: left'>
		<div style='float: right;'>
			<a href='?logout=1' class='btn btn-default glyphicon glyphicon-log-out'> Logout</a>
		</div>
	</div>
	<div>&nbsp;<!--Spacer--></div>
	<?php
		if (isset($precontent)) {
			echo $precontent;
		}
	?>
	
	<h2>Active Downloads</h2>
		<?php
			echo "<table class='table table-striped'>
			<tr><th></th><th>Filename</th><th>Download Link</th><th>Password</th><th>Posted By</th><th>Expires</th><th>Delete</th></tr>";
			
			//Listing all current links
			$result = $conn->query("SELECT token,path,password,id,uid,expires FROM files");
			while ($row = $result->fetch_assoc()) {
				//Get the user's name
				$userResult = $conn->query("SELECT fullname,email FROM user WHERE id='$row[uid]'");
				$r = $userResult->fetch_row();
				$fullname = $r[0];
				$email = $r[1];
				
				$humanExpiration = date("n/j/y g:ia") . " EST";
				$link = "http://168.192.1.20/webdrop/webdrop.php?token=$row[token]";
				
				$emailMessage = rawurlencode("Automatically generated email by Mor-Tech Webdrop\r\n\r\nFilename: $row[path]\r\nPosted By: $fullname ( $email )\r\nLink Expires: $humanExpiration\r\n\r\nDownload Link: $link\r\nPassword: $row[password]");
				$emailTitle = rawurlencode("Mor-Tech File Download: $row[path]");
				echo "<tr>
				<td><a href='mailto:?Subject=$emailTitle&body=$emailMessage' class='glyphicon glyphicon-envelope'></a></td>
				<td>$row[path]</td>
				<td><a href='$link'>$row[token]</a></td>
				<td>$row[password]</td>
				<td>$fullname</td>
				<td>$humanExpiration</td>
				<td><a href='?delete=$row[id]' class='glyphicon glyphicon-remove'></a></td>
				</tr>";
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
				$time = time();
				echo "<tr><td>$f</td><td><a href='?publish=$url&utc=$time'>Generate Link</a></td></tr>";
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
			
			//USER ACCOUNTS
			echo "<h2>Webdrop User Accounts</h2>
			Click a username to modify account settings<br>
			<table class='table'><tr>
			<th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Admin</th>
			</tr>";
			$result = $conn->query("SELECT * FROM user ORDER BY username ASC");
			while ($row = $result->fetch_assoc()) {
				echo "<tr>
				<td>$row[id]</td>
				<td><a href='?user=$row[id]'>$row[username]</a></td>
				<td>$row[fullname]</td>
				<td>$row[email]</td>
				<td>$row[admin]</td>
				</tr>";
			}
			echo "</table>";
		?>
	</div>
</body>