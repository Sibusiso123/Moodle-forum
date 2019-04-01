<?php
 $username = filter_input(INPUT_POST, 'STUDENT_NAME');
 $password = filter_input(INPUT_POST, 'STUDENT_PASSWORD');
 if (!empty($username)){
if (!empty($password)){
$host = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "d1477029";
// Create connection
$conn = new mysqli ($host, $dbusername, $dbpassword, $dbname);

if (mysqli_connect_error()){
die('Connect Error ('. mysqli_connect_errno() .') '
. mysqli_connect_error());
}
else{
$sql = "INSERT INTO STUDENTVOTES (STUDENT_NAME, password)
values ('$username','$password')";
if ($conn->query($sql)){
echo "New record is inserted sucessfully";
}
else{
echo "Error: ". $sql ."
". $conn->error;
}
$conn->close();
}
}
else{
echo "STUDENT_PASSWORD should not be empty";
die();
}
}
else{
echo "STUDENT_NAME should not be empty";
die();
}
?>
