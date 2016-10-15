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

	return json_decode($result);
}

function get_sentimental_analysis($content) {
    $url = 'https://gateway-a.watsonplatform.net/calls/url/URLGetCombinedData?apikey=cfe0e576ced36092902453304d79eb7e3603432f&url=https://www.ibm.com/us-en/&sentiment=1';
    $data = $content;

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
    return json_decode($result);
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

$response = $connection->get("statuses/user_timeline",
    ["screen_name" => $twitter_handle, "exclude_replies" => true, "count" => 500]);
$textForPI = "";
foreach ($response as $status) {
    $textForPI .= $status->text;
}

// 1.1 Send for PI and SA to IBM Watson API.
$sa = get_sentimental_analysis($textForPI);

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
			$businesses[$i]['wayfarer_scores']['personality']['Openness'] += $pi->tree->children[0]['children'][0]['children'][0]['percentage'];
			$businesses[$i]['wayfarer_scores']['personality']['Conscientiousness'] += $pi->tree->children[0]['children'][0]['children'][1]['percentage'];
			$businesses[$i]['wayfarer_scores']['personality']['Extraversion'] += $pi->tree->children[0]['children'][0]['children'][2]['percentage'];
			$businesses[$i]['wayfarer_scores']['personality']['Agreeableness'] += $pi->tree->children[0]['children'][0]['children'][3]['percentage'];
			$businesses[$i]['wayfarer_scores']['personality']['Neuroticism'] += $pi->tree->children[0]['children'][0]['children'][4]['percentage'];

			$businesses[$i]['wayfarer_scores']['needs']['Challenge'] += $pi->tree->children[1]['children'][0]['children'][0]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Closeness'] += $pi->tree->children[1]['children'][0]['children'][1]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Curiosity'] += $pi->tree->children[1]['children'][0]['children'][2]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Excitement'] += $pi->tree->children[1]['children'][0]['children'][3]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Harmony'] += $pi->tree->children[1]['children'][0]['children'][4]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Ideal'] += $pi->tree->children[1]['children'][0]['children'][5]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Liberty'] += $pi->tree->children[1]['children'][0]['children'][6]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Love'] += $pi->tree->children[1]['children'][0]['children'][7]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Practicality'] += $pi->tree->children[1]['children'][0]['children'][8]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Self-expression'] += $pi->tree->children[1]['children'][0]['children'][9]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Stability'] += $pi->tree->children[1]['children'][0]['children'][10]['percentage'];
			$businesses[$i]['wayfarer_scores']['needs']['Structure'] += $pi->tree->children[1]['children'][0]['children'][11]['percentage'];

			$businesses[$i]['wayfarer_scores']['values']['Conservation'] += $pi->tree->children[2]['children'][0]['children'][0]['percentage'];
			$businesses[$i]['wayfarer_scores']['values']['Openness to change'] += $pi->tree->children[2]['children'][0]['children'][1]['percentage'];
			$businesses[$i]['wayfarer_scores']['values']['Hedonism'] += $pi->tree->children[2]['children'][0]['children'][2]['percentage'];
			$businesses[$i]['wayfarer_scores']['values']['Self-enhancement'] += $pi->tree->children[2]['children'][0]['children'][3]['percentage'];
			$businesses[$i]['wayfarer_scores']['values']['Self-transcendence'] += $pi->tree->children[2]['children'][0]['children'][4]['percentage'];

		}
	}

	$businesses[$i]['wayfarer_scores']['personality']['Openness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['personality']['Conscientiousness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['personality']['Extraversion'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['personality']['Agreeableness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['personality']['Neuroticism'] /= count($businesses[$i]['reviews']);

	$businesses[$i]['wayfarer_scores']['needs']['Challenge'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Closeness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Curiosity'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Excitement'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Harmony'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Ideal'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Liberty'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Love'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Practicality'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Self-expression'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Stability'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['needs']['Structure'] /= count($businesses[$i]['reviews']);

	$businesses[$i]['wayfarer_scores']['values']['Conservation'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['values']['Openness to change'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['values']['Hedonism'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['values']['Self-enhancement'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_scores']['values']['Self-transcendence'] /= count($businesses[$i]['reviews']);
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($businesses);
