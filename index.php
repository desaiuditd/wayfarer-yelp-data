<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 15/10/16
 * Time: 09:04
 */
function get_personal_insights($text) {
	$url = 'https://d5020f3a-9667-4ff3-b084-b3f17ced03ed:YfHke0nzjrgB@gateway.watsonplatform.net/personality-insights/api/v2/profile';
	$data = $text;

	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header'  => "Content-type: text/plain\r\n",
			'method'  => 'POST',
			'content' => $data,
		),
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) { return false; }

	var_dump($result);

	return $result;
}

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
$textForPI = "";
foreach ($status as $response->statuses) {
    $textForPI .= $status->text;
}
var_dump($textForPI);

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
	$businesses[$i]['wayfarer_scores'] = array(
		'personality' => array(
			'Openness' => 0,
			'Conscientiousness' => 0,
			'Extraversion' => 0,
			'Agreeableness' => 0,
			'Neuroticism' => 0,
		),
		'needs' => array(
			'Challenge' => 0,
			'Closeness' => 0,
			'Curiosity' => 0,
			'Excitement' => 0,
			'Harmony' => 0,
			'Ideal' => 0,
			'Liberty' => 0,
			'Love' => 0,
			'Practicality' => 0,
			'Self-expression' => 0,
			'Stability' => 0,
			'Structure' => 0,
		),
		'values' => array(
			'Conservation' => 0,
			'Openness to change' => 0,
			'Hedonism' => 0,
			'Self-enhancement' => 0,
			'Self-transcendence' => 0,
		),
	);
	if ($result->num_rows > 0) {
		// output data of each row
		while($row = $result->fetch_assoc()) {
			$businesses[$i]['reviews'][] = $row;

			$text_for_pi = $row['review_text'];

			while (strlen($text_for_pi) <= 100) {
				$text_for_pi = $text_for_pi + $text_for_pi;
			}

			// PI API Call
			$pi = get_personal_insights($text_for_pi);
		}
	}
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($businesses);
