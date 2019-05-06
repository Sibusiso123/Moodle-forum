   <?php
	function tests($postlike,$postdislike){
  		if($postlike>$postdislike){
			return $postlike;
		}
		else if($postlike<$postdislike){
			return $postdislike;
		}
		else{
			return $postlike+$postdislike;
		}
	}
       ?>
