<?php
$dbhost = 'localhost';
$dbname='highcharts';
$dbuser='root';
$dbpass='';

try{
	$dbcon = new PDO("mysql:host={$dbhost};dbname={$dbname}",$dbuser,$dbpass);
	$dbcon->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $ex){
	die($ex	->getMessage());
}
$stmt=$dbcon->prepare("SELECT * FROM messages");
$stmt->execute();

$json =[];
while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	//echo $id;
	//echo $amount;
	$json[]=[$msg,(int)$up];
}
echo json_encode($json);

?>
