<?php
require_once "univs/UBC.php";
require_once "univs/CMU.php";
require_once "univs/UIUC.php";
require_once "univs/UMich.php";
require_once "univs/USC.php";
require_once "univs/UNC.php";
require_once "univs/OSU.php";
require_once "univs/WISC.php";
require_once "univs/MCM.php";

$ubc = new UBC();
$ubc->setup([
	'JSESSIONID' => 'XXXXXXXX-XXXXXXXXXXXXXXX'
]);
$ubc->login();
echo "\n\n\nUBC: \n";
check_update($ubc->get_status(), 'UBC');

$cmu = new CMU();
$cmu->setup([
	'JSESSIONID' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF-xx.xxxx',
	'_shibsession_ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff'
			=> '_ffffffffffffffffffffffffffffffff'
]);
$cmu->login();
echo "\n\n\nCMU: \n";
check_update( $cmu->get_status(), 'CMU');

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

$mcm = new MCM();
$mcm->setup('username', 'password');
$mcm->login();
echo "\n\n\nMCM: \n";
check_update($mcm->get_status(), 'MCM');

$osu = new OSU();
$osu->setup('username', 'password');
$osu->login();
echo "\n\n\nOSU: \n";
check_update( $osu->get_status(), 'OSU' );

$wisc = new WISC();
$wisc->setup('username', 'password');
$wisc->login();
echo "\n\n\nWISC: \n";
check_update($wisc->get_status(), 'WISC');

function check_update($result, $slug) {
	$prev = file_get_contents('/opt/admit/'.$slug);
	$prev = json_decode($prev, true);
	if(is_null($result) || !$result || !isset($result['sha'])){
		unset($prev['cookie']);
		file_put_contents('/opt/admit/'.$slug, json_encode($prev));
		return;
	}
	$result['data'] = trim($result['data']);
	$trim = $result;
	unset($trim['cookie']);
	var_dump( $trim );
	if(!$prev){
		$result['time_u'] = $result['updated_time'] = $result['time'] = time();
		file_put_contents('/opt/admit/'.$slug, json_encode($result));
		return;
	}
	if( (isset($prev['admitted']) || isset($prev['reject'])) && $prev['data'] == $result['data'] ) {
		return;
	}

	if($result['sha'] != $prev['sha']) {
		$append_data = $result['data'];
		if(isset($result['admitted'])) {
			$append_data = '录取！'.$result['data'];
		}
		if(isset($result['reject'])) {
			$append_data = '拒绝！'.$result['data'];
		}
		$data = urlencode($append_data);

		file_get_contents("https://maker.ifttt.com/trigger/admit/with/key/YOUR_KEY?value1={$slug}&value2={$result['data']}");
		if (isset($result['admitted']) || isset($result['reject']) || $slug == 'UMich'){
			// Special condition when admitted/rejected by a university.
			// e.g. Trigger a phone call, or send a tweet.
		}

		if(isset($prev['email'])){
			$result['email'] = $prev['email'];
		}
		$result['time_u'] = $result['time'] = $result['updated_time'] = time();
		if( $result['data'] != $prev['data'] ){
			$result['email'][$prev['time_u'] ?? $prev['time']] = $prev['data'];
		} else {
			$result['time_u'] = $prev['time_u'] ?? $prev['time'];
		}
		file_put_contents('/opt/admit/'.$slug, json_encode($result));
	} else {
		$prev['updated_time'] = time();
		if ( isset($result['cookie']) ){
			$prev['cookie'] = $result['cookie'];
		}
		if ( isset($result['data']) ){
			$prev['data'] = $result['data'];
		}
		if ( isset($result['html']) ){
			$prev['html'] = $result['html'];
		}
		file_put_contents('/opt/admit/'.$slug, json_encode($prev));
	}
}
