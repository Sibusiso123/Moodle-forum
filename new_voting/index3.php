<?php

  function number($num1,$num2){
	  if($num1>$num2){
		  $sum=0;
		return $num1+$num2;
	  }
	
	  else if($num1<$num2){
		  return $num2;
	  }
	  else if($num1==$num2){
		  return $num1;
	  }
	
  }
?>
