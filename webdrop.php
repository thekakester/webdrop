<?php
	include "util.php";
	include "sql.php";
	
	//Get the id of the file that should be downloaded
	$token = get("token");
	
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
.semitrans:hover {
	background-color: rgba(255,255,255,1);
}

.centered {
	position: absolute;
	width: 400px;
	top: 45%;
	left: 50%;
	transform: translate(-50%, -50%);
	text-align: center;
}

.watermark {
	color: #656;
}
</style>
<form action='' name='passform' method='post'>
	<div id='container' class='centered' style='opacity: 0;'>
		<div class="alert alert-danger" id='invalidPass' style='display: none'>Invalid password entered.  Please try again</div>
		<input id="password" name='password' type='text' class='form-control semitrans' onKeyUp='validate(this)'> <br>
		<div id='submit' class='btn btn-default' onClick='submit()' style='display: none'><span class='glyphicon glyphicon-save'></span> Download File</div>
		<input type='submit' value='Download' class='btn btn-default' style='display: none'>
	</div>
</form>

<div id='loading' class="progress centered" style='display: none'>
  <div class="progress-bar progress-bar-striped active" id='progressbar' role="progressbar" style="width: 0">
	Preparing Download...
  </div>
</div>
<script>
function validate(obj) {
	if (obj.value.length==8) {
		obj.style.backgroundColor='#bfb';
		$("#submit").slideDown(1000);
	} else if (obj.value.length > 8){
		obj.style.backgroundColor='#fbb';
		$("#submit").slideUp(250);
	} else {
		obj.style.backgroundColor='';
		$("#submit").slideUp(250);
	}
}

function submit() {
	$("#container").slideUp(250);
	$("#loading").slideDown(250);
	
	var percent = 0;
	var interval = setInterval(function() {
		percent+=1;
		$("#progressbar").css("width",percent + "%")
		if (percent == 100) {
			$("#progressbar").text("Your file is downloading")
			clearInterval(interval);
			passform.submit();
		}
	},100);
	
}

$('#container').animate({
    top: "50%",
	opacity: 1
  }, 1000);

//if blur and no value inside, set watermark text and class again.
var watermark = "Enter password to access this file";
$('#password').blur(function(){
	if ($(this).val().length == 0){
		$(this).val(watermark).addClass('watermark');
		$(this).attr('type', 'text');
	}
});

//if focus and text is watermrk, set it to empty and remove the watermark class
$('#password').focus(function(){
	if ($(this).val() == watermark){
		$(this).val('').removeClass('watermark');
		$(this).attr('type', 'password');
	}
}); 
$('#password').blur();
  
</script>
	
	
	<?php
	
	
	if (!$token) {
		//No file
		echo "Invalid link<br>";
		exit();
	}
	
	$password = post("password");
	if (!$password) {
		//echo "Please enter the unique password to download this file<br>";
		//echo "<form method='post' action=''>
		//			<input type='password' name='password'>
		//			<input type='submit' value='Submit'>
		//		</form>";
		exit();
	}
	
	$filepath = "";	//Where the file path will be stored
	
	//Check if this password matches the database password
	$stmt = $conn->prepare("SELECT path FROM files WHERE token=? AND password=? LIMIT 1");
	$stmt->bind_param("ss",$token,$password);
	$stmt->bind_result($filepath);
	$stmt->execute();
	
	if (!$stmt->fetch()) {
		echo "<script>$('#invalidPass').show(1000);</script>";
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
