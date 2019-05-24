   <?php
	function tests($postlike,$postdislike){
  		if($postlike<$postdislike){
			return $postdislike;
		}
// 		else if($postlike == $postdislike){
// 			echo "$postlike is $postdislike";
// 			return $postlike + $postdislike;
// 		}
		else if($postlike>$postdislike){
			 return $postdislike;
		}
		else{
			return $postlike;
		}
	}
       ?>
