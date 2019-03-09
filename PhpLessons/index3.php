<!DOCTYPE html>
<html>
<head>
	<title>Learning Php Part4</title>
</head>
<form method="GET">
	<input type="number" name="num1">
	<!--	<button>submit1</button> --> 
	<input type="number" name="num2"> 
	<button>submit</button>

</form>
	

<body> 

<?php
	//Comparison Operators

	//sleep(10);
	
	$num1=$_GET['num1'];
	$num2=$_GET['num2'];

	$Ans=$num1+$num1;

	if($num1<$num2){

		echo "Its true that num1 is less than num2"."<br/>";
	}

	if ($num1<0) {
		
		echo "num1 is negative"."<br/>";
	}
	if($num1>$num2){
		echo "num1 is greater than num2";
	}
	if($num1==$num2){
		echo "num1 is equal to num2";
	}
	 else{
	 	echo "<br/>"."No options";
	 }
	
	echo "<br/>".$Ans;
?>

</body>
</html>