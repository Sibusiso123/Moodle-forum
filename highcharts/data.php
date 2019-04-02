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
$stmt=$dbcon->prepare("SELECT * FROM highcharts");
$stmt->execute();

$json =[];
while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
	extract($row);
	//echo $id;
	//echo $amount;
	$json[]=[(string)$name,(int)$amount];
}
echo json_encode($json);

?>