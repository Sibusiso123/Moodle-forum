   <?php
	function tests($postlike,$postdislike){
  		if($postlike<$postdislike){
			return $postdislike;
		}
		else{
			return echo $postlike;
		}
	}
       ?>
