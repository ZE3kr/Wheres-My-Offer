<?php
header('Catch-Control: no-cache, must-revalidate, max-age=0, s-maxage=0');

if(!isset($_POST['from']) || !isset($_POST['subject']) || !isset($_POST['body'])) {
	exit();
}

$data = $_POST['subject'];
$from = $_POST['from'];

if(strstr($from, '@')){
	$from = explode('.', substr(strstr($from, '@'), 1));
	if($from[count($from)-2] == 'edu'){
		$from = strtoupper($from[count($from)-3]);
	} else {
		$from = strtoupper($from[count($from)-2]);
	}
}

$translate = [
	'UMICH' => 'UMich',
	'CORNELL' => 'Cornell',
	'ILLINOIS' => 'UIUC',
	'PURDUE' => 'Purdue',
];

if(isset($translate[$from])) {
	$from = $translate[$from];
}

$prev = file_get_contents('/opt/admit/'.$from);
$prev = json_decode($prev, true);

if(!$prev){
	exit();
}

if (strstr(strtolower($data.$_POST['body']), 'congrat')){
	$prev['admitted'] = true;
} else if (strstr(strtolower($data.$_POST['body']), 'waiting list') || strstr(strtolower($data.$_POST['body']), 'wait list')){
	$prev['waiting'] = true;
} else if(strstr(strtolower($data.$_POST['body']), 'reject') || strstr(strtolower($data.$_POST['body']), 'sorry')) {
	$prev['reject'] = true;
}

if( isset($_POST['time']) ){
	$time = $_POST['time'];
} else {
	$time = time();
}
$prev['email'][$time] = $data;
if($prev['time'] < $time) {
	$prev['time'] = $time;
}
$prev['updated_time'] = time();

$data = urlencode($data);

file_put_contents('/opt/admit/'.$from, json_encode($prev));
