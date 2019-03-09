<!DOCTYPE html>
<html>
<head>
	<title>Learning Php Part2</title>
</head>
<body>
	<?php

		sleep(4);
		//"<br/>" prints the next line.
		echo "String Length: ".strlen("Nazo bafo")."<br/>";//we use the strlen function to find out how many characters are in the string.
		echo  "Word Count: ".str_word_count("Nazo baba Emkay")."<br/>";
		echo "Reverse string: ".strrev("Emkay")."<br/>";//this reverses the string.
		echo "String position: ".strpos("This is Emkay","Emkay")."<br/>";//It gives the starting position.
		echo "String replace: ".str_replace("This is Emkay","Emkay", "Tumi");
	?>

</body>
</html>
