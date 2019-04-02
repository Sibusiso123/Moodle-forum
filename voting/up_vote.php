<?php
include("config.php");
$ip=$_SERVER['REMOTE_ADDR']; 
if($_POST['id'])
{
$id=$_POST['id'];
$id = mysqli_real_escape_string($bd,$id);
$ip_sql=mysqli_query($bd,"SELECT ip_add FROM voting_ip WHERE mes_id_fk='$id' AND ip_add='$ip'");
$count=mysqli_num_rows($ip_sql);
if($count==0)
{
$sql = "UPDATE messages SET up=up+1  WHERE mes_id='$id'";
mysqli_query( $bd,$sql);

////This part prevents a single user from liking a post multiple times//// 
$sql_in = "INSERT INTO voting_ip- (mes_id_fk,ip_add) VALUES ('$id','$ip')";
mysqli_query( $bd,$sql_in);

}
else
{
}
$result=mysqli_query($bd,"SELECT up FROM messages WHERE mes_id='$id'");
$row=mysqli_fetch_array($result);
$up_value=$row['up'];
echo $up_value; echo'&nbsp;&and;';
}
?>

