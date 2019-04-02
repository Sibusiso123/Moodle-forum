<?php
include("config.php");
$ip=$_SERVER['REMOTE_ADDR']; 
if($_POST['id'])
{
$id=$_POST['id'];
$id = mysql_escape_String($id);
$ip_sql=mysql_query("select ip_add from Voting_IP where mes_id_fk='$id' and ip_add='$ip'");
$count=mysql_num_rows($ip_sql);
if($count==0)
{
$sql = "update Messages set down=down+1  where mes_id='$id'";
mysql_query( $sql);

////////     Removing this provides a user to Dislike a Single Post Multiple Times    //////////
$sql_in = "insert into Voting_IP (mes_id_fk,ip_add) values ('$id','$ip')";
mysql_query( $sql_in);
//////////////////////////////////////////////////////////////////////////////////////////////

}
else
{
}
$result=mysql_query("select down from Messages where mes_id='$id'");
$row=mysql_fetch_array($result);
$down_value=$row['down'];
echo $down_value; echo'&nbsp;&or;';
}
?>
