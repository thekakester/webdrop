<?php
	include "util.php";
	include "sql.php";

	downloadFile();	//Put in a function to allow return to break out
	function downloadFile() {	
		global $conn;
		
		//Get the id of the file that should be downloaded
		$token = get("token");
		if (!$token) {
			//No file
			//echo "Invalid link<br>";
			return;
		}
		
		$password = post("password");
		if (!$password) {
			//echo "Please enter the unique password to download this file<br>";
			//echo "<form method='post' action=''>
			//			<input type='password' name='password'>
			//			<input type='submit' value='Submit'>
			//		</form>";
			return;
		}
		
		$filepath = "";	//Where the file path will be stored
		
		//Check if this password matches the database password
		$stmt = $conn->prepare("SELECT path FROM files WHERE token=? AND password=? LIMIT 1");
		$stmt->bind_param("ss",$token,$password);
		$stmt->bind_result($filepath);
		$stmt->execute();
		
		if (!$stmt->fetch()) {
			echo "<script>var invalidPass=true</script>";
			return;
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
		exit();
	}

	
	include "jqueryBootstrap.php";
?>
	

	
<body>
<style>
body {
	background-image: url("background.jpg");
	-webkit-background-size: cover;
	-moz-background-size: cover;
	-o-background-size: cover;
	background-size: cover;
}
.semitrans {
	text-align: center;
	background-color: rgba(255,255,255,0.7);
	-webkit-transition-property: background-color; /* Safari */
    -webkit-transition-duration: 0.3s; /* Safari */
    transition-property: background-color;
    transition-duration: 0.3s;
}
.semitrans:hover, .semitrans:focus {
	background-color: rgba(255,255,255,1);
}

.centered {
	position: absolute;
	padding-left: 10px;
	padding-right: 10px;
	padding-top: 10px;
	padding-bottom: 10px;
	top: 45%;
	left: 50%;
	transform: translate(-50%, -50%);
	background-color: rgba(255,255,255,.9);
}

.watermark {
	color: #999;
}
</style>
<div class='centered' id='container' style='opacity: 0;'>
	<img src='logo.png'><br><br>
	<font size='+3'>New_Mortech_Logo.png</font> (192kb)<br>
	From: Mitch Davis (<a href='mailto:mdavis@mortechdesign.com'>mdavis@mortechdesign.com</a>)<br>
	Expires: 5/31/2016 10:15pm EST<br>
	
	<div style='text-align: center; width: 100%'>
		<img src='lock.png'><br>
		This file is password protected<br>
		Enter password before downloading file<br>
	</div><br>
	
	<form action='' name='passform' id='passform' method='post' onSubmit="return submitForm()">
		<div id='passwordcontainer' style='width: 100%; text-align: center;'>
			<div class="alert alert-danger" id='invalidPass' style='display: none'>Invalid password entered.  Please try again</div>
			<script> if (invalidPass==true){$("#invalidPass").show(1000);}</script>
			<div id='thisliterallyjustholdsthepasswordbox' style='width: 50%; margin-left: auto; margin-right: auto;'>
				<input id="password" name='password' type='text' class='form-control semitrans' onKeyUp='validate(this)' onChange='validate(this)'> <br>
			</div>
			<div id='submit' class='btn btn-default' onClick='passform.submitButton.click()' style='display: none'><span class='glyphicon glyphicon-save'></span> Download File</div>
			<input type='submit' name='submitButton' value='Download File' class='btn btn-default' style='display: none'>
		</div>
	</form>

	<div id='loading' class="progress" style='display: none'>
	  <div class="progress-bar progress-bar-striped active" id='progressbar' role="progressbar" style="width: 0">
		Preparing Download...
	  </div>
	</div>
	
	<div width='100%' style='text-align: center'>
		<font color=#777>WebDrop was designed in-house at Mortech Design &copy; 2016</font>
	</div>
</div>
<script>
function validate(obj) {
	if (obj.value.length==8) {
		obj.style.backgroundColor='#bfb';
		$("#submit").slideDown(1000);
	} else if (obj.value.length > 8 &&  !ignoreValidation){
		obj.style.backgroundColor='#fbb';
		$("#submit").slideUp(250);
	} else {
		obj.style.backgroundColor='';
		$("#submit").slideUp(250);
	}
}

//There's a chance that someone uses only the mouse to paste a password in.
//Make sure the site reacts to that
setInterval(function(){validate(document.getElementById('password'));},500);

function submitForm() {
	$("#passwordcontainer").slideUp(250);
	$("#loading").slideDown(250);
	
	var percent = 0;
	var interval = setInterval(function() {
		percent+=1;
		$("#progressbar").css("width",percent + "%")
		if (percent == 100) {
			$("#progressbar").text("Your file is downloading")
			clearInterval(interval);
			document.getElementById("passform").submit();
		}
	},100);
	
	return false;
}

$('#container').animate({
    top: "50%",
	opacity: 1
  }, 1000);

//if blur and no value inside, set watermark text and class again.
var watermark = " Password ";
var ignoreValidation = true;
$('#password').blur(function(){
	if ($(this).val().length == 0){
		$(this).val(watermark).addClass('watermark');
		$(this).attr('type', 'text');
		ignoreValidation = true;
	}
});

//if focus and text is watermrk, set it to empty and remove the watermark class
$('#password').focus(function(){
	if ($(this).val() == watermark){
		$(this).val('').removeClass('watermark');
		$(this).attr('type', 'password');
		ignoreValidation = false;
	}
}); 
$('#password').blur();
  
</script>
