<?php include('server.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upvote and Downvote</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <link rel="stylesheet" href="main.css">
</head>
<body>
  <div class="posts-wrapper">
   <?php foreach ($posts as $post): ?>
   	<div class="post">
      <?php echo $post['text']; ?>
      <div class="post-info">
	    <!-- if user likes post, style button differently -->
      	<i <?php if (userLiked($post['id'])): ?>
      		  class="fa fa-arrow-circle-up like-btn"
      	  <?php else: ?>
      		  class="fa fa-arrow-circle-o-up like-btn"
      	  <?php endif ?>
      	  data-id="<?php echo $post['id'] ?>"></i>
      	<span class="likes"><?php echo getLikes($post['id']); ?></span>

      	&nbsp;&nbsp;&nbsp;&nbsp;

	    <!-- if user dislikes post, style button differently -->
      	<i
      	  <?php if (userDisliked($post['id'])): ?>
      		  class="fa fa-arrow-circle-down dislike-btn"
      	  <?php else: ?>
      		  class="fa fa-arrow-circle-o-down dislike-btn"
      	  <?php endif ?>
      	  data-id="<?php echo $post['id'] ?>"></i>
      	<span class="dislikes"><?php echo getDislikes($post['id']); ?></span>
      </div>
      <span class="likes"><p></p>    <?php echo "Total Number of Votes so far : "; echo(getLikes($post['id'])+ getDislikes($post['id']));
      ?></span>
      <?php
        if(getLikes($post['id'])+ getDislikes($post['id']) == 0){
          echo "<br/>"."<br/>"."No Student has voted";
              //echo "<br/>".max(array(getLikes($post['id'])+ getDislikes($post['id'])));
        }
        else if(getLikes($post['id'])+ getDislikes($post['id']) == 1){
          echo "<br/>"."<br/>"."A Student has voted";
              //echo "<br/>".max(array(getLikes($post['id'])+ getDislikes($post['id'])));
        }
        else if(getLikes($post['id'])+ getDislikes($post['id']) >1){
          echo "<br/>"."<br/>"."Some Students have voted";
              //echo "<br/>".max(array(getLikes($post['id'])+ getDislikes($post['id'])));
        }
	function tests(){
  		return	echo max(array(getLikes($post['id'])+ getDislikes($post['id'])));
	}
        // else {
        //   echo max(getLikes($post['id'])+ getDislikes($post['id']));
        // }
       ?>
   	</div>
   <?php endforeach ?>
  </div>
  <script src="scripts.js"></script>
</body>
</html>
