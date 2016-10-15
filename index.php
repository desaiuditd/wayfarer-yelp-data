<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 15/10/16
 * Time: 09:04
 */

include_once "../ee-config.php";
require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;
define ('CONSUMER_KEY', "w3Bsm5zzNf9GBsZ6aO7zxGWhC");
define ('CONSUMER_SECRET', "YNakI4IOV3tiRNlBbxrpuXWaYVhyNOPHOVxJM2zD3VRARm4UjK");
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
$access_token = "2440917456-2mJtKDy6LJEvANfsQIR3KxdU3udyNHKxqZR8HAQ";
$access_token_secret = "TL8acUKIJXaUPEACs2Tei2XxFcCJLI70VzosCsZTqwBch";
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret);
$content = $connection->get("account/verify_credentials");

$response = $connection->get("search/tweets", ["q" => $twitter_handle]);
var_dump($response);

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
	FROM business AS b
	WHERE b.city LIKE '%" . $city . "%'
	ORDER BY b.stars DESC
	LIMIT 5";
$result = $conn->query($sql);

$businesses = array();

if ($result->num_rows > 0) {
	// output data of each row
	while($row = $result->fetch_assoc()) {
		$businesses[] = $row;
	}
}

foreach ($businesses as $i => $b) {
	$sql = "SELECT *
		FROM review AS r
		WHERE r.business_id = '" . $b['business_id'] . "'
		LIMIT 5";
	$result = $conn->query($sql);
	$businesses[$i]['reviews'] = array();
	if ($result->num_rows > 0) {
		// output data of each row
		while($row = $result->fetch_assoc()) {
			$businesses[$i]['reviews'][] = $row;
		}
	}
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($businesses);
