<?php
// © Nick Ubels & Alex-Jan Sigtermans
// Used by Study Assosication Cover with permission
// http://nickubels.nl

// Set PHP variables
date_default_timezone_set('Europe/Amsterdam');

## Defining API URL
define('apiURL', 'http://v0.ovapi.nl/');

	
## Grabbing page from API
function grabPage($path){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$path);
	curl_setopt($ch, CURLOPT_FAILONERROR,1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	$retValue = curl_exec($ch);
	// echo curl_error($ch);
	curl_close($ch);
	return $retValue;
}
## Getting JSON out of CURL
function getJSON($url){
	return json_decode(grabPage($url),TRUE);
}
## Fetching TPC data
function fetchTimingPoint($timingPointCode) {
	return getJSON(apiURL.'/tpc/'.$timingPointCode.'');
}
## Fetching SAC data
function fetchStopArea($stopAreaCode) {
	$data = getJSON(apiURL.'/stopareacode/'.$stopAreaCode.'');

	// To make compatible with other functions, go one level deep into array
	//return $data;
	return $data[$stopAreaCode];
}
## Fetching data from specific journey
function fetchJourney($journeycode){
	return getJSON(apiURL.'/journey/'.$journeycode.'');
}
function sortArraysByField(&$array, $field){
	usort($array, function($a, $b) use ($field) { return strnatcmp($a[$field], $b[$field]); });
}

function processTime(&$dateTime){
	if(substr($dateTime, -8) == '00:00:00'){
		$date = new DateTime($dateTime);
		$now = new DateTime();
		$diff = $date->diff($now);
		if($diff->format('%h') >= 4){
			$date->add(new DateInterval('P1D'));
			$dateTime = date_format($date,'Y-m-d').'T'.date_format($date,'H:i:s');
		}
	}
	$dateTime = str_replace('T', ' ', $dateTime);
}

function calcDelay($expected,$target){
	$expected = new DateTime($expected);
	$target = new DateTime($target);
	$diff = date_diff($expected,$target);
	if($diff->s <= 29){
		$diff->s = 0;
		if($diff->s == 0 && $diff->i == 0 && $diff->h == 0){
			$diff->invert = 0;
		}
	}
	if($diff->s >= 30) {
		$diff->s = 0;
		if($diff->i == 59){
			$diff->h = $diff->h+1;
		}
		else{
			$diff->i = $diff->i+1; }

	}
	$delay = $diff->h*60+$diff->i;
	$invert = $diff->invert;
	if($invert == 1){
		$status = '2';
	}
	elseif($delay == 0){
		$status = '1';
	}
	else{
		$status = '0';
	}
	$result = array('delay' => $delay, 'status' => $status);
	return $result;
	// status: [0] = early, [1] = ontime, [2] = delay
}

function calcMinutes($time){
	$now = date('Y-m-d H:i:s');
	$now = new DateTime($now);
	$time = new DateTime($time);

	$diff = date_diff($now,$time);
	$minutes = $diff->h*60+$diff->i;

	return $minutes;
}
function getStopData($data){
	$stops = array();
	foreach($data as $stop){
		if(gettype($stop) == 'array'){
			array_push($stops, $stop['Stop']);
		}
	}
	return $stops;
}
function getDepartures($data,$sort=true){
	// Get all departures from (a set of) timing point code(s), possibly sorted by departure time
	$departures = array();
	foreach($data as $stop){
		if(gettype($stop) == 'array'){
			foreach($stop['Passes'] as $departure){
				if($departure['JourneyStopType'] != 'LAST' && $departure['TripStopStatus'] != 'PASSED'){
					processTime($departure['ExpectedArrivalTime']);
					processTime($departure['TargetArrivalTime']);
					processTime($departure['ExpectedDepartureTime']);
					processTime($departure['TargetDepartureTime']);
					array_push($departures, $departure);
				}
			}
		}
	}

	if($sort){
		sortArraysByField($departures, 'ExpectedDepartureTime');
	}

	return $departures;
}


function getDelayOnly($delay,$status){
	if($status == 0){

		$delay = '(-'.$delay.')';
	}
	if($status == 1){
		$delay = '';//  ('.$delay.' min.) eruit gehaald
	}
	if($status == 2){
		$delay = '(+'.$delay.')';
	}
	return $delay;
}
function getMinutesLabel($status, $minutes, $atstop){
	if($status == 0){

		$status = '<div class="label label-info '.$cancel.'">'.$minutes.'</div>';
	}
	if($status == 1){
		$status = '<div class="label label-success '.$cancel.'">'.$minutes.'</div>';
	}
	if($status == 2){
		$status = '<div class="label label-danger '.$cancel.'">'.$minutes.'</div>';
	}
	return $status;
}

function qbuzz_color($linePlanningNumber,$linePublicNumber){
	return '<span class="'.$linePlanningNumber.'">'.$linePublicNumber.'</span>';
}

function outputDepartures($departures){
	global $auth;
	$max = 5;
	$output = '
		<div class="container">
			<table>
				<tr>
					<th>Line</th>
					<th class="destination">Destination</th>
					<th class="destination">Departure</th>
				</tr>
				<tr>
					<td><br /></td>
				</tr>
	';
	for($i=0;$i <= $max; $i++){
		$departure = $departures[$i];
		$delay = calcDelay($departure['ExpectedDepartureTime'],$departure['TargetDepartureTime']);
		$minutes = calcMinutes($departure['ExpectedDepartureTime']);
		if($departure['LinePlanningNumber'] == NULL){
			continue;
		}
		$output .= '
				<tr>
					<td class="small">'.qbuzz_color($departure['LinePlanningNumber'],$departure['LinePublicNumber']).'</td>
					';
					if($departure['TripStopStatus'] == "CANCELLED"){
						$output .= '
						<td class="destination cancelled">'.$departure['DestinationName50'].'</td>
						<td class="small destination">'.date_format(date_create($departure['TargetDepartureTime']), 'H:i').' cancelled</td>';
					} else {
						$output .= '<td class="destination">'.$departure['DestinationName50'].'</td>';
						if($departure['TripStopStatus'] == "UNKNOWN"){
						$output .= '<td class="small destination">'.date_format(date_create($departure['TargetDepartureTime']), 'H:i').'</td>';
						} else { // for PLANNED and DRIVING statusses
							if($minutes == 0){ // bus icon for due buses
								$output .= '<td class="small destination"><img src="icon.png"></td>';
							} else{
								$output .= '<td class="small destination">'.$minutes.' min</td>';
							}
						}
					}
					$output .='
				</tr>
				<tr>
					<td><br /><br /></td>
				</tr>
		';
	}
	$output .= '</table></div>';
	return $output;
}
?>

<style type="text/css">
.container { 
	text-align: center;
}

table {
	text-align: center;
	width: 100%;
    margin: 0 auto;
	font-size: 55px;
}

tr.bordered {
    border-bottom: 1px solid #111;
}

td.small {
	width: 20%;
}
.destination {
	text-align: left;
}
.cancelled{
	text-decoration: line-through;
}
.g509 {
	color: #F289B7;
	font-weight: bold;
}

.g501 {
	color: #71BF44;
	font-weight: bold;
}

.g556 {
	color: #F37121;
	font-weight: bold;
}

.g517 {
	color: #F6911E;
	font-weight: bold;
}

</style>
<div style="text-align: center; width:100%;height:100%">
	<h2 style="font-size: 80px; margin: 80px 0;">Departing buses</h2>

<?php

// Code voor aanroepen 

	$data = fetchTimingPoint('10004130,10004140');
	$departures = getDepartures($data);
	//echo outputGeneralMessage($data);
	echo outputDepartures($departures);
?>

</div>
