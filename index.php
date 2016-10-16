<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 15/10/16
 * Time: 09:04
 */

// Fields to amplify with weight in Cool Mode.
// Challenge
// Excitement
// Openness to change

include('./httpful.phar');

function score_correlation($arr1, $arr2) {
	$correlation = 0;

	$k = sum_product_mean_deviation( $arr1, $arr2);
	$ssmd1 = sum_square_mean_deviation( $arr1);
	$ssmd2 = sum_square_mean_deviation( $arr2);

	$product = $ssmd1 * $ssmd2;

	$res = sqrt($product);

	$correlation = $k / $res;

	return $correlation;
}

function sum_product_mean_deviation( $arr1, $arr2) {
	$sum = 0;

	$num = count($arr1);

	for($i=0; $i<$num; $i++) {
		$sum = $sum + product_mean_deviation( $arr1, $arr2, $i);
	}

	return $sum;
}

function product_mean_deviation( $arr1, $arr2, $item) {
	return ( mean_deviation( $arr1, $item) * mean_deviation( $arr2, $item));
}

function sum_square_mean_deviation( $arr) {
	$sum = 0;

	$num = count($arr);

	for($i=0; $i<$num; $i++) {
		$sum = $sum + square_mean_deviation( $arr, $i);
	}

	return $sum;
}

function square_mean_deviation( $arr, $item) {
	return mean_deviation( $arr, $item) * mean_deviation( $arr, $item);
}

function sum_mean_deviation( $arr) {
	$sum = 0;

	$num = count($arr);

	for($i=0; $i<$num; $i++) {
		$sum = $sum + mean_deviation( $arr, $i);
	}

	return $sum;
}

function mean_deviation( $arr, $item) {
	$average = average( $arr);

	return $arr[$item] - $average;
}

function average( $arr) {
	$sum = sum( $arr);
	$num = count($arr);

	return $sum/$num;
}

function sum( $arr) {
	return array_sum($arr);
}

function get_personal_insights($text) {
	$url = 'https://gateway.watsonplatform.net/personality-insights/api/v2/profile';
	$data = $text;

	$result = \Httpful\Request::post($url)
								->authenticateWith('d5020f3a-9667-4ff3-b084-b3f17ced03ed', 'YfHke0nzjrgB')
	                            ->body($data)
	                            ->sends(\Httpful\Mime::PLAIN)
	                            ->send();

	return json_decode($result);
}

function get_sentimental_analysis($content) {
    $url = 'https://gateway-a.watsonplatform.net/calls/url/URLGetCombinedData?apikey=cfe0e576ced36092902453304d79eb7e3603432f';
    $data = $content;

	$result = \Httpful\Request::post($url)
								->body($data)
								->sends(\Httpful\Mime::PLAIN)
								->send();

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

$is_cool_mode = !empty($_GET['cool_mode']);

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

$twitter_pi     = get_personal_insights($textForPI);
$twitter_scores = array();
if ($twitter_pi && $twitter_pi->tree && $twitter_pi->tree && $twitter_pi->tree->children) {
	$twitter_scores[ 'personality'][ 'Openness'] = $twitter_pi->tree->children[ 0]->children[ 0]->children[ 0]->percentage;
	$twitter_scores[ 'personality'][ 'Conscientiousness'] = $twitter_pi->tree->children[ 0]->children[ 0]->children[ 1]->percentage;
	$twitter_scores[ 'personality'][ 'Extraversion'] = $twitter_pi->tree->children[ 0]->children[ 0]->children[ 1]->percentage;
	$twitter_scores[ 'personality'][ 'Agreeableness'] = $twitter_pi->tree->children[ 0]->children[ 0]->children[ 2]->percentage;
	$twitter_scores[ 'personality'][ 'Neuroticism'] = $twitter_pi->tree->children[ 0]->children[ 0]->children[ 3]->percentage;

	$twitter_scores[ 'needs'][ 'Challenge'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 0]->percentage;
	$twitter_scores[ 'needs'][ 'Closeness'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 1]->percentage;
	$twitter_scores[ 'needs'][ 'Curiosity'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 2]->percentage;
	$twitter_scores[ 'needs'][ 'Excitement'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 3]->percentage;
	$twitter_scores[ 'needs'][ 'Harmony'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 4]->percentage;
	$twitter_scores[ 'needs'][ 'Ideal'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 5]->percentage;
	$twitter_scores[ 'needs'][ 'Liberty'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 6]->percentage;
	$twitter_scores[ 'needs'][ 'Love'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 7]->percentage;
	$twitter_scores[ 'needs'][ 'Practicality'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 8]->percentage;
	$twitter_scores[ 'needs'][ 'Self-expression'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 9]->percentage;
	$twitter_scores[ 'needs'][ 'Stability'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 10]->percentage;
	$twitter_scores[ 'needs'][ 'Structure'] = $twitter_pi->tree->children[ 1]->children[ 0]->children[ 11]->percentage;

	$twitter_scores[ 'values'][ 'Conservation'] = $twitter_pi->tree->children[ 2]->children[ 0]->children[ 0]->percentage;
	$twitter_scores[ 'values'][ 'Openness to change'] = $twitter_pi->tree->children[ 2]->children[ 0]->children[ 1]->percentage;
	$twitter_scores[ 'values'][ 'Hedonism'] = $twitter_pi->tree->children[ 2]->children[ 0]->children[ 2]->percentage;
	$twitter_scores[ 'values'][ 'Self-enhancement'] = $twitter_pi->tree->children[ 2]->children[ 0]->children[ 3]->percentage;
	$twitter_scores[ 'values'][ 'Self-transcendence'] = $twitter_pi->tree->children[ 2]->children[ 0]->children[ 4]->percentage;
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
	$businesses[$i]['wayfarer_review_scores'] = array(
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
			while (str_word_count($text_for_pi) <= 100) {
				$text_for_pi = $text_for_pi . $text_for_pi;
			}

			// PI API Call
			$pi = get_personal_insights($text_for_pi);

			if ($pi && $pi->tree && $pi->tree && $pi->tree->children) {
				$businesses[$i]['wayfarer_review_scores']['personality']['Openness'] += $pi->tree->children[0]->children[0]->children[0]->percentage;
				$businesses[$i]['wayfarer_review_scores']['personality']['Conscientiousness'] += $pi->tree->children[0]->children[0]->children[1]->percentage;
				$businesses[$i]['wayfarer_review_scores']['personality']['Extraversion'] += $pi->tree->children[0]->children[0]->children[1]->percentage;
				$businesses[$i]['wayfarer_review_scores']['personality']['Agreeableness'] += $pi->tree->children[0]->children[0]->children[2]->percentage;
				$businesses[$i]['wayfarer_review_scores']['personality']['Neuroticism'] += $pi->tree->children[0]->children[0]->children[3]->percentage;

				$challenge = ($is_cool_mode) ? 5 * $pi->tree->children[1]->children[0]->children[0]->percentage : $pi->tree->children[1]->children[0]->children[0]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Challenge'] += $challenge;
				$businesses[$i]['wayfarer_review_scores']['needs']['Closeness'] += $pi->tree->children[1]->children[0]->children[1]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Curiosity'] += $pi->tree->children[1]->children[0]->children[2]->percentage;
				$excitement = ($is_cool_mode) ? 5 * $pi->tree->children[1]->children[0]->children[3]->percentage : $pi->tree->children[1]->children[0]->children[3]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Excitement'] += $excitement;
				$businesses[$i]['wayfarer_review_scores']['needs']['Harmony'] += $pi->tree->children[1]->children[0]->children[4]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Ideal'] += $pi->tree->children[1]->children[0]->children[5]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Liberty'] += $pi->tree->children[1]->children[0]->children[6]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Love'] += $pi->tree->children[1]->children[0]->children[7]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Practicality'] += $pi->tree->children[1]->children[0]->children[8]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Self-expression'] += $pi->tree->children[1]->children[0]->children[9]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Stability'] += $pi->tree->children[1]->children[0]->children[10]->percentage;
				$businesses[$i]['wayfarer_review_scores']['needs']['Structure'] += $pi->tree->children[1]->children[0]->children[11]->percentage;

				$businesses[$i]['wayfarer_review_scores']['values']['Conservation'] += $pi->tree->children[2]->children[0]->children[0]->percentage;
				$openness = ($is_cool_mode) ? 5 * $pi->tree->children[2]->children[0]->children[1]->percentage : $pi->tree->children[2]->children[0]->children[1]->percentage;
				$businesses[$i]['wayfarer_review_scores']['values']['Openness to change'] += $openness;
				$businesses[$i]['wayfarer_review_scores']['values']['Hedonism'] += $pi->tree->children[2]->children[0]->children[2]->percentage;
				$businesses[$i]['wayfarer_review_scores']['values']['Self-enhancement'] += $pi->tree->children[2]->children[0]->children[3]->percentage;
				$businesses[$i]['wayfarer_review_scores']['values']['Self-transcendence'] += $pi->tree->children[2]->children[0]->children[4]->percentage;
			}

		}
	}

	$total = ($is_cool_mode) ? (count($businesses[$i]['reviews']) + 4) : count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['personality']['Openness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['personality']['Conscientiousness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['personality']['Extraversion'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['personality']['Agreeableness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['personality']['Neuroticism'] /= count($businesses[$i]['reviews']);

	$businesses[$i]['wayfarer_review_scores']['needs']['Challenge'] /= $total;
	$businesses[$i]['wayfarer_review_scores']['needs']['Closeness'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Curiosity'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Excitement'] /= $total;
	$businesses[$i]['wayfarer_review_scores']['needs']['Harmony'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Ideal'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Liberty'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Love'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Practicality'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Self-expression'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Stability'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['needs']['Structure'] /= count($businesses[$i]['reviews']);

	$businesses[$i]['wayfarer_review_scores']['values']['Conservation'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['values']['Openness to change'] /= $total;
	$businesses[$i]['wayfarer_review_scores']['values']['Hedonism'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['values']['Self-enhancement'] /= count($businesses[$i]['reviews']);
	$businesses[$i]['wayfarer_review_scores']['values']['Self-transcendence'] /= count($businesses[$i]['reviews']);
}

usort($businesses, function($a, $b) {
	$a_scores = array();
	$b_scores = array();
	$t_scores = array();

	global $twitter_scores;

	$a_scores = array_merge($a_scores, array_values($a['wayfarer_review_scores']['personality']));
	$a_scores = array_merge($a_scores, array_values($a['wayfarer_review_scores']['needs']));
	$a_scores = array_merge($a_scores, array_values($a['wayfarer_review_scores']['values']));

	$b_scores = array_merge($b_scores, array_values($b['wayfarer_review_scores']['personality']));
	$b_scores = array_merge($b_scores, array_values($b['wayfarer_review_scores']['needs']));
	$b_scores = array_merge($b_scores, array_values($b['wayfarer_review_scores']['values']));

	$t_scores = array_merge($t_scores, array_values($twitter_scores['personality']));
	$t_scores = array_merge($t_scores, array_values($twitter_scores['needs']));
	$t_scores = array_merge($t_scores, array_values($twitter_scores['values']));

	$at_corr = score_correlation($a_scores, $t_scores);
	$bt_corr = score_correlation($b_scores, $t_scores);

	var_dump($at_corr);
	var_dump($bt_corr);

	return $at_corr > $bt_corr;
});

$conn->close();

header('Content-Type: application/json');
echo json_encode(
	array(
		'wayfarer_twitter_scores' => $twitter_scores,
		'businesses' => $businesses,
	)
);
