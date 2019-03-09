<!DOCTYPE html>
<html>
<head>
	<title>Learning Php</title>
</head>
<form method="GET">  
	<input type="text" name="name">
	<button>SUBMIT</button>
</form>
	
<body> 
	<!-- <img src="neymar.jpg"   width="500" height="500"> -->

<?php
	
	 //echo This allows you to display the string onto the browser.
	//print "EMKAY";//This will print the string in the same line as echo did above.

	$name =$_GET['name']; 
	echo "This is ".$name; //This is how we combine a string with variable.

?>
</body>
</html>

