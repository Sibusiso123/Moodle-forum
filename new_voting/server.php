<?php
// connect to database
//$highcharts;
$conn = mysqli_connect('127.0.0.1', 'root','Emkaydatabase');
//$conn = mysqli_connect('localhost', 'root','');//Emkaydatabase is the name of my database.
 $query = mysqli_query("SELECT * FROM post_id ");


// lets assume a user is logged in with id $user_id
// $m1;
// $m2;
$user_id = 13;//This is the user_id of the student that will be voting.

if (!$conn) {
  die("Error connecting to database: " . mysqli_connect_error($conn));
  exit();
}



// if user clicks like or dislike button
if (isset($_POST['action'])) {
  $post_id = $_POST['post_id'];
  $action = $_POST['action'];
  switch ($action) {
  	case 'like':
         $sql="INSERT INTO rating_info (user_id, post_id, rating_action)
         	   VALUES ($user_id, $post_id, 'like')
         	   ON DUPLICATE KEY UPDATE rating_action='like'"; //rating_info is the name of the table for the info.
         break;
  	case 'dislike':
          $sql="INSERT INTO rating_info (user_id, post_id, rating_action)
               VALUES ($user_id, $post_id, 'dislike')
         	   ON DUPLICATE KEY UPDATE rating_action='dislike'";
         break;
  	case 'unlike':
	      $sql="DELETE FROM rating_info WHERE user_id=$user_id AND post_id=$post_id";
	      break;
  	case 'undislike':
      	  $sql="DELETE FROM rating_info WHERE user_id=$user_id AND post_id=$post_id";
      break;
  	default:
  		break;
  }

  // execute query to effect changes in the database ...
  mysqli_query($conn, $sql);
  echo getRating($post_id);
  exit(0);
}
function test($user_id,$user_id1){
	if($user_id>=$user_id1){
	return $user_id+$user_id1;
	}
	//return 1;
}
// Get total number of likes for a particular post
function getLikes($m1,$id)
{
	if($m1!=$id){
	return $m1 + $id;
	}
  global $conn;
  $sql = "SELECT COUNT(*) FROM rating_info
  		  WHERE post_id = $id AND rating_action='like'";
  $rs = mysqli_query($conn, $sql);
  $result = mysqli_fetch_array($rs);
  $result[0];
}

// Get total number of dislikes for a particular post
function getDislikes($id,$a)
{
	if($a<$id){
	 return $id;
	}
	else if($a>$id){
	return $a;
	}
	else if($a==$id){
	return $a + $id;
	}
	else if($a>0){
	   return $a;
	}
  global $conn;
  $sql = "SELECT COUNT(*) FROM rating_info
  		  WHERE post_id = $id AND rating_action='dislike'";
  $rs = mysqli_query($conn, $sql);
  $result = mysqli_fetch_array($rs);
 $result[0];
}

// Get total number of likes and dislikes for a particular post
function getRating($id)
{
  global $conn;
  $rating = array();
  $likes_query = "SELECT COUNT(*) FROM rating_info  WHERE post_id = $id AND rating_action='like'";
  $dislikes_query = "SELECT COUNT(*) FROM rating_info
		  			WHERE post_id = $id AND rating_action='dislike'";
  $likes_rs = mysqli_query($conn, $likes_query);
  $dislikes_rs = mysqli_query($conn, $dislikes_query);
  $likes = mysqli_fetch_array($likes_rs);
  $dislikes = mysqli_fetch_array($dislikes_rs);
  $rating = [
  	'likes' => $likes[0],
  	'dislikes' => $dislikes[0]
  ];
  return json_encode($rating);
}

// Check if user already likes post or not
function userLiked($post_id)
{
  global $conn;
  global $user_id;
  $sql = "SELECT * FROM rating_info  WHERE user_id=$user_id
  		  AND post_id=$post_id AND rating_action='like'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
  	return true;
  }
  else{
  	return false;
  }
}

// Check if user already dislikes post or not
function userDisliked($post_id)
{
  global $conn;
  global $user_id;
  $sql = "SELECT * FROM rating_info  WHERE user_id=$user_id
  		  AND post_id=$post_id AND rating_action='dislike'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
  	return true;
  }else{
  	return false;
  }
}
// $sql = "SELECT * FROM rating_info order by post_id desc";
// $result = mysqli_query($conn, $sql);
// $rating_info  = mysqli_fetch_all($result, MYSQLI_ASSOC);


$sql = "SELECT
  c.id,
  c.text,
  COUNT(*) AS total
FROM posts AS c
  INNER JOIN rating_info AS b ON b.post_id = c.id
GROUP BY c.id
ORDER BY total DESC";
$result = mysqli_query($conn, $sql);
// fetch all posts from database
// return them as an associative array called $posts
$posts  = mysqli_fetch_all($result);
