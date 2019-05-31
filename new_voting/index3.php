<?php

  function number($num1,$num2){
	  if($num1>$num2 && $num1 < 4){
		  $sum=0;
		return $num1+$num2;
	  }
	
	  else if($num1<$num2 && $num2 > 4){
		  return $num2;
	  }
	  else if ($num1==$num2 || $num == 0){
		  return $num1;
	  }
  }
?>
