<?php
	if (!isset($conn)) {
		echo "Page does not exist";
		exit();
	}

	$user = post('username');
	$pass = post('password');
	if ($user && $pass) {
		//Look them up!
		$dbpass = $uid = "";
		$stmt = $conn->prepare("SELECT id,password FROM user WHERE username=?");
		$stmt->bind_param("s",$user);
		$stmt->bind_result($uid, $dbpass);
		$stmt->execute();
		
		if ($stmt->fetch()) {
			//Compare the passwords
			if ($dbpass == crypt($pass,$dbpass)) {
				//They're logged in!
				$_SESSION['uid'] = $uid;
				header("Location: admin.php");
			}
		}
		
		echo "<script>var invalid = true; var failed = true;</script>";
	}
?>

<body style='background-color: #ddd'>
	<div class='container' style='background-color:#fff;'>
		<div class='jumbotron'>
			<h2>Web Drop Admin Login</h2>
		</div>
		<script>
			if (invalid) {
				document.write("<div class='bg-danger text-danger'>Invalid username or password</div>");
			}
		</script>
		<form name='login' action='' method='post' class='form-inline'>
			<input type='text' name='username' class='form-control' placeholder='Username'>
			<input type='password' name='password' class='form-control' placeholder='Password'>
			<input type='submit' name='submit' value='Login' class='form-control btn btn-info'>
		</form>
	</div>
</body>