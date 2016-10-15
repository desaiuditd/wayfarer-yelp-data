<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 15/10/16
 * Time: 09:04
 */

include_once "../ee-config.php";

$twitter_handle = $_GET['twitter_handle'];
$city = $_GET['city'];
$city = '%'.$city.'%';

if ( empty($twitter_handle) || empty($city) ) {
	header('Content-Type: application/json');
	$json = array(
		'error_code' => 400,
		'message' => 'Data Missing. Pass `twitter_handle` & `city` values as query parameters: ' . $conn->connect_error,
	);
	echo json_encode($json);
	exit();
}

// 1. Take twitter handle & fetch tweets

// 2. Take city & fetch reviews

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

$sql = "SELECT *
FROM review AS r 
JOIN business AS b
ON r.business_id = b.business_id
WHERE city LIKE '%" . $city . "%'
GROUP BY b.business_id
ORDER BY b.stars DESC
LIMIT 50";
$result = $conn->query($sql);

$reviews = array();

if ($result->num_rows > 0) {
	// output data of each row
	while($row = $result->fetch_assoc()) {
		$reviews[] = $row;
	}
}
$conn->close();

header('Content-Type: application/json');
echo json_encode($reviews);
