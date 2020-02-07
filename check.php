<?php
require_once "UIUC.php";
require_once "UMich.php";
require_once "USC.php";
require_once "UNC.php";

$uiuc = new UIUC();
$uiuc->setup('username', 'password');
$uiuc->login();
echo "\n\n\nUIUC: \n";
check_update( $uiuc->get_status(), 'UIUC');

$umich = new UMich();
$umich->setup('username', 'password');
$umich->login();
echo "\n\n\nUMich: \n";
check_update( $umich->get_status(), 'UMich');

$USC = new USC();
$USC->setup('username', 'password');
$USC->login();
echo "\n\n\nUSC: \n";
check_update( $USC->get_status(), 'USC' );

$UNC = new UNC();
$UNC->setup('username', 'password');
$UNC->login();
echo "\n\n\nUNC: \n";
check_update( $UNC->get_status(), 'UNC' );

function check_update($result, $slug) {
	$prev = file_get_contents('/opt/admit/'.$slug);
	$prev = json_decode($prev, true);
	if(is_null($result) || !$result || !isset($result['sha'])){
		unset($prev['cookie']);
		file_put_contents('/opt/admit/'.$slug, json_encode($prev));
		return;
	}
	$result['data'] = trim($result['data']);
	var_dump( $result );

	if($result['sha'] != $prev['sha']) {
		file_put_contents('/opt/admit/'.$slug, json_encode($result));
		$result['data'] = urlencode($result['data']);
		file_get_contents("https://maker.ifttt.com/trigger/admit/with/key/YOUR_KEY?value1={$slug}&value2={$result['data']}");
	}
}
