<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Voting with jQuery, Ajax and PHP</title>
<style>
*{margin:0px; padding:0px}
body{background:url(../background1.png); font-family:Gotham, "Helvetica Neue", Helvetica, Arial, sans-serif; text-align:center}
h1 {  font-family: Helvetica, Arial, sans-serif;  text-align: center; font-size:50px; margin-top:50px; color:#fff;
	  text-shadow: 2px 2px 0px rgba(255,255,255,.7), 5px 7px 0px rgba(0, 0, 0, 0.1); 
}
#main{height:150px;margin:20px auto;width:570px; background:#fff}

.up{height:36px; width:56px; font-size:18px; text-align:center; background:#00AF09; margin-bottom:2px; margin:10px auto}
.up a{color:#fff;text-decoration:none; line-height:1.8em}

.down{height:36px; width:56px; font-size:18px; text-align:center; background:#EC383B; margin-bottom:2px; margin:10px auto; margin-top:20px}
.down a{color:#FFFFFF;text-decoration:none; line-height:1.8em}

.box1{float:left; height:40px; width:100px; color:#000}

.box2{float:left; width:400px; text-align:left;margin-left:30px;height:60px;margin-top:10px;font-size:16px; color:#fff; }

img{border:none;padding-top:7px;}
</style>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">
$(function() {
$(".vote").click(function() 
{
var id = $(this).attr("id");
var name = $(this).attr("name");
var dataString = 'id='+ id ;
var parent = $(this);
if(name=='up')
{
$.ajax({
   type: "POST",
   url: "up_vote.php",
   data: dataString,
   cache: false,

   success: function(html)
   {
    parent.html(html);
  
  }  });
}
else if(name=='down')
{
$.ajax({
   type: "POST",
   url: "down_vote.php",
   data: dataString,
   cache: false,

   success: function(html)
   {
       parent.html(html);
  }  
 });
}
return false;
	});

});
</script>
</head>

<body>
<h1>Voting with jQuery, Ajax and PHP</h1>
<?php
include('config.php');
$sql=mysqli_query($bd,"SELECT * FROM messages  LIMIT 9");
$row=mysqli_fetch_array($sql);
while($row=mysqli_fetch_array($sql))
{
$msg=$row['msg'];
$mes_id=$row['mes_id'];
$up=$row['up'];
$down=$row['down'];
?>

<div id="main">

<div class="box1">
	<div class='up'><a href="" class="vote" id="<?php echo $mes_id; ?>" name="up"><?php echo $up; ?> &and;</a></div>
    <p style="color:#777; margin-left:10px; margin-top:-10px">Like(s)</p>
    
	<div class='down'><a href="" class="vote" id="<?php echo $mes_id; ?>" name="down"><?php echo $down; ?> &or;</a></div>
    <p style="color:#777; margin-left:10px; margin-top:-10px">Dislike(s)</p>
</div>

<div class='box2' ><p style="color:#777"><?php echo $msg; ?></p></div>

</div>
<?php
}
?>
</body>
</html>
