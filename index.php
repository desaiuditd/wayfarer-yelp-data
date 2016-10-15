<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 15/10/16
 * Time: 09:04
 */

include_once "../ee-config.php";

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
// Check connection
if ($conn->connect_error) {
	header('Content-Type: application/json');
	$json = array(
		'error_code' => 500,
		'message' => 'Connection failed: ' . $conn->connect_error,
	);
	echo json_encode($json);
	exit();
}

$sql = "SELECT * FROM review LIMIT 10";
$result = $conn->query($sql);

$reviews = array();
var_dump($result->num_rows);
if ($result->num_rows > 0) {
	// output data of each row
	while($row = $result->fetch_assoc()) {
		var_dump($row);
		$reviews[] = $row;
	}
}
$conn->close();

header('Content-Type: application/json');
echo json_encode($reviews);
